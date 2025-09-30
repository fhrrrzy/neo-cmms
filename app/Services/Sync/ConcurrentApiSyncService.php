<?php

namespace App\Services\Sync;

use App\Models\Plant;
use App\Models\ApiSyncLog;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\Station;
use App\Models\RunningTime;
use App\Models\WorkOrder;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;
use App\Services\Sync\Processors\EquipmentProcessor;
use App\Services\Sync\Processors\EquipmentMaterialProcessor;
use App\Services\Sync\Processors\EquipmentWorkOrderProcessor;
use App\Services\Sync\Processors\RunningTimeProcessor;
use App\Services\Sync\Processors\WorkOrderProcessor;

class ConcurrentApiSyncService
{
    protected int $timeoutSeconds = 60;
    protected array $allowedPlantCodes = [];

    /**
     * Sync all data types concurrently using Http::pool()
     */
    public function syncAllConcurrently(array $plantCodes = [], ?string $runningTimeStartDate = null, ?string $runningTimeEndDate = null, ?string $workOrderStartDate = null, ?string $workOrderEndDate = null, ?array $types = null): array
    {
        // Get plant codes if not provided
        if (empty($plantCodes)) {
            $plantCodes = Plant::where('is_active', true)->pluck('plant_code')->toArray();
        }

        // Store allowed plant codes for filtering
        $this->allowedPlantCodes = $plantCodes;

        // Set default dates (running time: previous month to today; work orders: previous month to today)
        $runningTimeStartDate = $runningTimeStartDate ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $runningTimeEndDate = $runningTimeEndDate ?? Carbon::today()->toDateString();
        $workOrderStartDate = $workOrderStartDate ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $workOrderEndDate = $workOrderEndDate ?? Carbon::today()->toDateString();

        $this->info("🚀 Starting concurrent sync for " . count($plantCodes) . " plants");
        $this->info("APIs: Equipment, Running Time ({$runningTimeStartDate} to {$runningTimeEndDate}), Work Orders ({$workOrderStartDate} to {$workOrderEndDate})");

        $startTime = now();

        try {
            // Make concurrent API requests using Http::pool()
            $responses = $this->makeConcurrentApiRequests($plantCodes, $runningTimeStartDate, $runningTimeEndDate, $workOrderStartDate, $workOrderEndDate, $types);

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
    protected function makeConcurrentApiRequests(array $plantCodes, string $runningTimeStartDate, string $runningTimeEndDate, string $workOrderStartDate, string $workOrderEndDate, ?array $types = null): array
    {
        $baseUrl = rtrim(config('ims.base_url'), '/');
        $token = config('ims.token');

        $this->info("🔄 Making concurrent API requests (equipment, materials & work orders)...");
        $selected = collect($types ?? ['equipment', 'equipment_material', 'equipment_work_orders', 'work_orders'])->keyBy(fn($v) => $v);

        // Batch 1: dynamically include requests based on selected types
        $batch1 = Http::pool(function (Pool $pool) use ($baseUrl, $token, $plantCodes, $workOrderStartDate, $workOrderEndDate, $selected) {
            $equipmentsUrl = $baseUrl . '/equipments';
            $materialsUrl = $baseUrl . '/equipments/material?start_date=' . urlencode($workOrderStartDate) . '&end_date=' . urlencode($workOrderEndDate);
            $eqWorkOrdersUrl = $baseUrl . '/equipments/work-order?start_date=' . urlencode($workOrderStartDate) . '&end_date=' . urlencode($workOrderEndDate);
            $workOrdersUrl = $baseUrl . '/work-order?start_date=' . urlencode($workOrderStartDate) . '&end_date=' . urlencode($workOrderEndDate);

            $requests = [];
            if ($selected->has('equipment')) {
                $this->info("GET {$equipmentsUrl} (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->asJson()
                    ->send('GET', $equipmentsUrl, [
                        'json' => [
                            'plant' => array_values($plantCodes),
                        ],
                    ]);
            }
            if ($selected->has('equipment_material')) {
                $this->info("GET {$materialsUrl} (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->asJson()
                    ->send('GET', $materialsUrl, [
                        'json' => [
                            'plant' => array_values($plantCodes),
                            'material_number' => '000000',
                        ],
                    ]);
            }
            if ($selected->has('equipment_work_orders')) {
                $this->info("GET {$eqWorkOrdersUrl} (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->asJson()
                    ->send('GET', $eqWorkOrdersUrl, [
                        'json' => [
                            'plant' => array_values($plantCodes),
                            'material_number' => '000000',
                        ],
                    ]);
            }
            if ($selected->has('work_orders')) {
                $this->info("GET {$workOrdersUrl} (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->asJson()
                    ->send('GET', $workOrdersUrl, [
                        'json' => [
                            'plant' => array_values($plantCodes),
                        ],
                    ]);
            }

            return $requests;
        });

        $this->info("🔄 Making concurrent API requests (running time per plant): " . count($plantCodes) . " requests...");

        // Batch 2: running time per plant (one request per plant)
        $runningTimeResponses = Http::pool(function (Pool $pool) use ($baseUrl, $token, $plantCodes, $runningTimeStartDate, $runningTimeEndDate) {
            $requests = [];
            foreach (array_values($plantCodes) as $index => $plant) {
                $query = http_build_query([
                    'start_date' => $runningTimeStartDate,
                    'end_date' => $runningTimeEndDate,
                ]);
                $url = $baseUrl . '/equipments/jam-jalan?' . $query;
                $this->info("GET {$url} (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->asJson()
                    ->send('GET', $url, [
                        'json' => [
                            'plant' => [$plant],
                        ],
                    ]);
            }
            return $requests;
        });

        $this->info("✅ All API requests completed");

        $mapped = [];
        $i = 0;
        foreach (['equipment', 'equipment_material', 'equipment_work_orders', 'work_orders'] as $key) {
            if ($selected->has($key)) {
                $mapped[$key] = $batch1[$i] ?? null;
                $i++;
            } else {
                $mapped[$key] = null;
            }
        }
        $mapped['running_time_batches'] = $runningTimeResponses;
        return $mapped;
    }

    /**
     * Process API responses and sync to database
     */
    protected function processApiResponses(array $responses): array
    {
        $results = [
            'equipment' => ['processed' => 0, 'success' => 0, 'failed' => 0],
            'equipment_material' => ['processed' => 0, 'success' => 0, 'failed' => 0],
            'equipment_work_orders' => ['processed' => 0, 'success' => 0, 'failed' => 0],
            'running_time' => ['processed' => 0, 'success' => 0, 'failed' => 0],
            'work_orders' => ['processed' => 0, 'success' => 0, 'failed' => 0],
        ];

        // Process equipment, equipment_material, equipment_work_orders and work orders responses
        foreach (['equipment', 'equipment_material', 'equipment_work_orders', 'work_orders'] as $apiType) {
            $response = $responses[$apiType] ?? null;
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

        // Process running time batch responses (aggregate items across plants)
        $this->info("🔄 Processing running_time data (batched by plant)...");
        $allRtItems = [];
        $rtResponses = $responses['running_time_batches'] ?? [];
        foreach ($rtResponses as $rtResponse) {
            try {
                if ($rtResponse->successful()) {
                    $data = $rtResponse->json() ?? [];
                    $items = $data['data'] ?? $data;
                    if (!empty($items) && is_array($items)) {
                        $allRtItems = array_merge($allRtItems, $items);
                    }
                }
            } catch (\Throwable $e) {
                // continue; individual failures shouldn't break the whole batch
            }
        }

        $this->info("running_time aggregated items: " . count($allRtItems));
        if (!empty($allRtItems)) {
            $rtResult = $this->processApiData('running_time', $allRtItems);
            $results['running_time'] = $rtResult;
            $this->info("✅ running_time: processed={$rtResult['processed']}, success={$rtResult['success']}, failed={$rtResult['failed']}");
        } else {
            $this->info("ℹ️ running_time: No data returned");
            $results['running_time'] = ['processed' => 0, 'success' => 0, 'failed' => 0];
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
                                (new EquipmentProcessor())->process($item);
                                break;
                            case 'equipment_material':
                                (new EquipmentMaterialProcessor())->process($item, $this->allowedPlantCodes);
                                break;
                            case 'equipment_work_orders':
                                (new EquipmentWorkOrderProcessor())->process($item, $this->allowedPlantCodes);
                                break;
                            case 'running_time':
                                (new RunningTimeProcessor())->process($item, $this->allowedPlantCodes);
                                break;
                            case 'work_orders':
                                (new WorkOrderProcessor())->process($item);
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
