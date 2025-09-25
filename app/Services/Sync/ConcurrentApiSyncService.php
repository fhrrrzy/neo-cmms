<?php

namespace App\Services\Sync;

use App\Models\Plant;
use App\Models\ApiSyncLog;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\RunningTime;
use App\Models\WorkOrder;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;

class ConcurrentApiSyncService
{
    protected int $timeoutSeconds = 60;
    protected array $allowedPlantCodes = [];

    /**
     * Sync all data types concurrently using Http::pool()
     */
    public function syncAllConcurrently(array $plantCodes = [], ?string $runningTimeStartDate = null, ?string $runningTimeEndDate = null, ?string $workOrderStartDate = null, ?string $workOrderEndDate = null): array
    {
        // Get plant codes if not provided
        if (empty($plantCodes)) {
            $plantCodes = Plant::where('is_active', true)->pluck('plant_code')->toArray();
        }

        // Store allowed plant codes for filtering
        $this->allowedPlantCodes = $plantCodes;

        // Set default dates
        $runningTimeStartDate = $runningTimeStartDate ?? Carbon::yesterday()->toDateString();
        $runningTimeEndDate = $runningTimeEndDate ?? Carbon::yesterday()->toDateString();
        $workOrderStartDate = $workOrderStartDate ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $workOrderEndDate = $workOrderEndDate ?? Carbon::today()->toDateString();

        $this->info("🚀 Starting concurrent sync for " . count($plantCodes) . " plants");
        $this->info("APIs: Equipment, Running Time ({$runningTimeStartDate} to {$runningTimeEndDate}), Work Orders ({$workOrderStartDate} to {$workOrderEndDate})");

        $startTime = now();

        try {
            // Make concurrent API requests using Http::pool()
            $responses = $this->makeConcurrentApiRequests($plantCodes, $runningTimeStartDate, $runningTimeEndDate, $workOrderStartDate, $workOrderEndDate);

            // Process responses
            $results = $this->processApiResponses($responses);

            $duration = now()->diffInSeconds($startTime);
            $this->info("✅ Concurrent sync completed in {$duration} seconds");

            return $results;
        } catch (Exception $e) {
            $duration = now()->diffInSeconds($startTime);
            $this->error("❌ Concurrent sync failed after {$duration} seconds: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Make concurrent API requests using Http::pool()
     */
    protected function makeConcurrentApiRequests(array $plantCodes, string $runningTimeStartDate, string $runningTimeEndDate, string $workOrderStartDate, string $workOrderEndDate): array
    {
        $baseUrl = rtrim(config('ims.base_url'), '/');
        $token = config('ims.token');

        $this->info("🔄 Making 3 concurrent API requests...");

        $responses = Http::pool(function (Pool $pool) use ($baseUrl, $token, $plantCodes, $runningTimeStartDate, $runningTimeEndDate, $workOrderStartDate, $workOrderEndDate) {
            return [
                $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->get($baseUrl . '/equipments', ['plant' => $plantCodes]),

                $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->get($baseUrl . '/equipments/jam-jalan', [
                        'plant' => $plantCodes,
                        'start_date' => $runningTimeStartDate,
                        'end_date' => $runningTimeEndDate,
                    ]),

                $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->get($baseUrl . '/work-order', [
                        'plant' => $plantCodes,
                        'start_date' => $workOrderStartDate,
                        'end_date' => $workOrderEndDate,
                    ]),
            ];
        });

        $this->info("✅ All API requests completed");

        // Http::pool returns a numerically-indexed array; remap to explicit API keys
        return [
            'equipment' => $responses[0] ?? null,
            'running_time' => $responses[1] ?? null,
            'work_orders' => $responses[2] ?? null,
        ];
    }

    /**
     * Process API responses and sync to database
     */
    protected function processApiResponses(array $responses): array
    {
        $results = [
            'equipment' => ['processed' => 0, 'success' => 0, 'failed' => 0],
            'running_time' => ['processed' => 0, 'success' => 0, 'failed' => 0],
            'work_orders' => ['processed' => 0, 'success' => 0, 'failed' => 0],
        ];

        // Process each API response
        foreach ($responses as $apiType => $response) {
            $this->info("🔄 Processing {$apiType} data...");

            try {
                if ($response->successful()) {
                    $data = $response->json() ?? [];
                    $items = $data['data'] ?? $data;

                    Log::info("API {$apiType} response structure", [
                        'has_data_key' => isset($data['data']),
                        'data_type' => gettype($items),
                        'items_count' => is_array($items) ? count($items) : 'not_array',
                        'sample_item' => is_array($items) && !empty($items) ? array_keys($items[0]) : 'no_items'
                    ]);

                    if (!empty($items) && is_array($items)) {
                        $result = $this->processApiData($apiType, $items);
                        $results[$apiType] = $result;

                        $this->info("✅ {$apiType}: processed={$result['processed']}, success={$result['success']}, failed={$result['failed']}");
                    } else {
                        $this->info("ℹ️ {$apiType}: No data returned");
                        $results[$apiType] = ['processed' => 0, 'success' => 0, 'failed' => 0];
                    }
                } else {
                    throw new Exception("HTTP {$response->status()}: {$response->body()}");
                }
            } catch (Exception $e) {
                $this->error("❌ {$apiType}: " . $e->getMessage());
                $results[$apiType] = ['processed' => 0, 'success' => 0, 'failed' => 0, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Process data for a specific API type
     */
    protected function processApiData(string $apiType, array $items): array
    {
        // Map API key to canonical sync_type values stored in api_sync_logs
        $syncType = $apiType === 'work_orders' ? 'work_order' : $apiType;

        $log = ApiSyncLog::create([
            'sync_type' => $syncType,
            'status' => 'running',
            'sync_started_at' => now(),
        ]);

        $processed = 0;
        $success = 0;
        $failed = 0;

        try {
            DB::transaction(function () use ($items, $apiType, &$processed, &$success, &$failed) {
                foreach ($items as $item) {
                    $processed++;
                    try {
                        switch ($apiType) {
                            case 'equipment':
                                $this->processEquipmentItem($item);
                                break;
                            case 'running_time':
                                $this->processRunningTimeItem($item);
                                break;
                            case 'work_orders':
                                $this->processWorkOrderItem($item);
                                break;
                        }
                        $success++;
                    } catch (\Throwable $e) {
                        $failed++;
                        report($e);
                    }
                }
            });

            $log->update([
                'status' => 'completed',
                'records_processed' => $processed,
                'records_success' => $success,
                'records_failed' => $failed,
                'sync_completed_at' => now(),
            ]);
        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sync_completed_at' => now(),
            ]);
            throw $e;
        }

        return [
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Process equipment item
     */
    protected function processEquipmentItem(array $item): void
    {
        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }
        if (!$plant) {
            throw new \RuntimeException('Plant not found: ' . (string) $plantCode);
        }

        $groupName = trim((string) (Arr::get($item, 'group_name') ?? Arr::get($item, 'equipment_group')));
        $equipmentGroup = null;
        if ($groupName !== '') {
            $equipmentGroup = EquipmentGroup::firstOrCreate(['name' => $groupName], [
                'description' => null,
                'is_active' => true,
            ]);
        }

        Equipment::updateOrCreate(
            ['equipment_number' => Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR')],
            [
                'plant_id' => $plant->id,
                'equipment_group_id' => $equipmentGroup?->id,
                'company_code' => Arr::get($item, 'company_code') ?? Arr::get($item, 'BUKRS'),
                'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'description') ?? Arr::get($item, 'EQKTU'),
                'object_number' => Arr::get($item, 'object_number') ?? Arr::get($item, 'OBJNR'),
                'point' => Arr::get($item, 'point') ?? Arr::get($item, 'POINT'),
                'api_created_at' => ($ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT')) ? Carbon::parse($ts) : null,
                'is_active' => true,
            ]
        );
    }

    /**
     * Process running time item
     */
    protected function processRunningTimeItem(array $item): void
    {
        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
        // If we have an allowed list (requested plants), skip anything outside it
        if (!empty($this->allowedPlantCodes) && ($plantCode === null || !in_array($plantCode, $this->allowedPlantCodes, true))) {
            Log::debug('Skipping running time record - Plant not in allowed list', ['plant_code' => $plantCode]);
            return;
        }
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }

        // Skip if plant not found instead of throwing exception
        if (!$plant) {
            Log::warning("Skipping running time record - Plant not found: {$plantCode}");
            return;
        }

        $equipmentNumber = Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR');
        $date = Arr::get($item, 'date') ?? Arr::get($item, 'DATE');

        if (!$equipmentNumber || !$date) {
            throw new Exception("Missing required fields: equipment_number={$equipmentNumber}, date={$date}");
        }

        Log::info("Processing running time: equipment={$equipmentNumber}, date={$date}, plant={$plantCode}");

        $apiId = Arr::get($item, 'api_id') ?? Arr::get($item, 'ID');

        // Prefer matching by api_id when present. If not present in DB yet, fall back to equipment_number+date
        $runningTime = null;
        if ($apiId) {
            $runningTime = RunningTime::where('api_id', $apiId)->first();
        }
        if (!$runningTime) {
            $runningTime = RunningTime::where('equipment_number', $equipmentNumber)
                ->where('date', $date)
                ->first();
        }

        $attributes = [
            'equipment_number' => $equipmentNumber,
            'date' => $date,
            'plant_id' => $plant->id,
            'date_time' => Arr::get($item, 'date_time') ?? Arr::get($item, 'DATE_TIME'),
            'running_hours' => Arr::get($item, 'running_hours') ?? Arr::get($item, 'RECDV'),
            'counter_reading' => Arr::get($item, 'counter_reading') ?? Arr::get($item, 'CNTRR'),
            'maintenance_text' => Arr::get($item, 'maintenance_text') ?? Arr::get($item, 'MDTXT'),
            'company_code' => Arr::get($item, 'company_code') ?? Arr::get($item, 'BUKRS'),
            'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'EQKTU'),
            'object_number' => Arr::get($item, 'object_number') ?? Arr::get($item, 'OBJNR'),
            'api_created_at' => ($ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT')) ? Carbon::parse($ts) : null,
        ];
        if ($apiId) {
            $attributes['api_id'] = (string) $apiId;
        }

        if ($runningTime) {
            $runningTime->fill($attributes)->save();
        } else {
            $runningTime = RunningTime::create($attributes);
        }

        Log::info("Running time record saved successfully: ID={$runningTime->id}");
    }

    /**
     * Process work order item
     */
    protected function processWorkOrderItem(array $item): void
    {
        $plantCode = Arr::get($item, 'plant');
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }

        WorkOrder::updateOrCreate(
            ['order' => Arr::get($item, 'order')],
            [
                'ims_id' => Arr::get($item, 'id'),
                'mandt' => Arr::get($item, 'mandt'),
                'order_type' => Arr::get($item, 'order_type'),
                'created_on' => Arr::get($item, 'created_on') ? Carbon::parse(Arr::get($item, 'created_on')) : null,
                'change_date_for_order_master' => Arr::get($item, 'change_date_for_order_master') ? Carbon::parse(Arr::get($item, 'change_date_for_order_master')) : null,
                'description' => Arr::get($item, 'description'),
                'company_code' => Arr::get($item, 'company_code'),
                'plant_id' => $plant?->id,
                'plant_code' => $plantCode,
                'responsible_cctr' => Arr::get($item, 'responsible_cctr'),
                'order_status' => Arr::get($item, 'order_status'),
                'technical_completion' => Arr::get($item, 'technical_completion') ? Carbon::parse(Arr::get($item, 'technical_completion')) : null,
                'cost_center' => Arr::get($item, 'cost_center'),
                'profit_center' => Arr::get($item, 'profit_center'),
                'object_class' => Arr::get($item, 'object_class'),
                'main_work_center' => Arr::get($item, 'main_work_center'),
                'notification' => Arr::get($item, 'notification'),
                'cause' => Arr::get($item, 'cause'),
                'cause_text' => Arr::get($item, 'cause_text'),
                'code_group_problem' => Arr::get($item, 'code_group_problem'),
                'item_text' => Arr::get($item, 'item_text'),
                'created' => Arr::get($item, 'created') ? Carbon::parse(Arr::get($item, 'created')) : null,
                'released' => Arr::get($item, 'released') ? Carbon::parse(Arr::get($item, 'released')) : null,
                'completed' => Arr::get($item, 'completed'),
                'closed' => Arr::get($item, 'closed') ? Carbon::parse(Arr::get($item, 'closed')) : null,
                'planned_release' => Arr::get($item, 'planned_release') ? Carbon::parse(Arr::get($item, 'planned_release')) : null,
                'planned_completion' => Arr::get($item, 'planned_completion') ? Carbon::parse(Arr::get($item, 'planned_completion')) : null,
                'planned_closing_date' => Arr::get($item, 'planned_closing_date') ? Carbon::parse(Arr::get($item, 'planned_closing_date')) : null,
                'release' => Arr::get($item, 'release') ? Carbon::parse(Arr::get($item, 'release')) : null,
                'close' => Arr::get($item, 'close') ? Carbon::parse(Arr::get($item, 'close')) : null,
                'api_updated_at' => Arr::get($item, 'updated_at') ? Carbon::parse(Arr::get($item, 'updated_at')) : null,
            ]
        );
    }

    /**
     * Log info message
     */
    protected function info(string $message): void
    {
        Log::info($message);
        echo $message . PHP_EOL;
    }

    /**
     * Log error message
     */
    protected function error(string $message): void
    {
        Log::error($message);
        echo $message . PHP_EOL;
    }
}
