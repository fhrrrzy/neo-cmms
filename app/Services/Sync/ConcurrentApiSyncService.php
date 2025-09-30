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
            $equipmentsQuery = http_build_query(['plant' => array_values($plantCodes)]);
            $workOrdersQuery = http_build_query([
                'start_date' => $workOrderStartDate,
                'end_date' => $workOrderEndDate,
                'plant' => array_values($plantCodes),
            ]);

            $equipmentsUrl = $baseUrl . '/equipments?' . $equipmentsQuery;
            $materialsUrl = $baseUrl . '/equipments/material';
            $eqWorkOrdersUrl = $baseUrl . '/equipments/work-order';
            $workOrdersUrl = $baseUrl . '/work-order?' . $workOrdersQuery;

            $requests = [];
            if ($selected->has('equipment')) {
                $this->info("GET " . $equipmentsUrl);
                $requests[] = $pool->withHeaders(['Authorization' => $token])->timeout($this->timeoutSeconds)->get($equipmentsUrl);
            }
            if ($selected->has('equipment_material')) {
                $this->info("POST " . $materialsUrl . " (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])->timeout($this->timeoutSeconds)->asJson()->post($materialsUrl, [
                    'plant' => array_values($plantCodes),
                    'material_number' => '000000',
                    'start_date' => $workOrderStartDate,
                    'end_date' => $workOrderEndDate,
                ]);
            }
            if ($selected->has('equipment_work_orders')) {
                $this->info("POST " . $eqWorkOrdersUrl . " (JSON body)");
                $requests[] = $pool->withHeaders(['Authorization' => $token])->timeout($this->timeoutSeconds)->asJson()->post($eqWorkOrdersUrl, [
                    'plant' => array_values($plantCodes),
                    'material_number' => '000000',
                    'start_date' => $workOrderStartDate,
                    'end_date' => $workOrderEndDate,
                ]);
            }
            if ($selected->has('work_orders')) {
                $this->info("GET " . $workOrdersUrl);
                $requests[] = $pool->withHeaders(['Authorization' => $token])->timeout($this->timeoutSeconds)->get($workOrdersUrl);
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
                    'plant' => [$plant],
                ]);
                $url = $baseUrl . '/equipments/jam-jalan?' . $query;
                $this->info("GET " . $url);
                $requests[] = $pool->withHeaders(['Authorization' => $token])
                    ->timeout($this->timeoutSeconds)
                    ->get($url);
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
                                $this->processEquipmentItem($item);
                                break;
                            case 'equipment_material':
                                $this->processEquipmentMaterialItem($item);
                                break;
                            case 'equipment_work_orders':
                                $this->processEquipmentWorkOrderItem($item);
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

        $station = null;
        $kostl = Arr::get($item, 'cost_center') ?? Arr::get($item, 'KOSTL');
        if ($kostl) {
            $station = Station::where('plant_id', $plant->id)
                ->where('cost_center', $kostl)
                ->first();
        }

        Equipment::updateOrCreate(
            ['equipment_number' => Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR')],
            [
                'plant_id' => $plant->id,
                'station_id' => $station?->id,
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

        // Prefer matching by ims_id when present. If not present in DB yet, fall back to equipment_number+date
        $runningTime = null;
        if ($apiId) {
            $runningTime = RunningTime::where('ims_id', $apiId)->first();
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
            $attributes['ims_id'] = (string) $apiId;
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

        $stationId = null;
        $woCostCenter = Arr::get($item, 'cost_center');
        if ($plant && $woCostCenter) {
            $s = Station::where('plant_id', $plant->id)->where('cost_center', $woCostCenter)->first();
            $stationId = $s?->id;
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
                'station_id' => $stationId,
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
     * Process equipment work order item
     */
    protected function processEquipmentWorkOrderItem(array $item): void
    {
        $plantCode = Arr::get($item, 'plant');
        if (!$plantCode) {
            throw new Exception('Missing plant in equipment_work_orders item');
        }
        if (!empty($this->allowedPlantCodes) && !in_array($plantCode, $this->allowedPlantCodes, true)) {
            return;
        }

        $plant = Plant::where('plant_code', $plantCode)->first();
        if (!$plant) {
            throw new Exception('Plant not found for equipment_work_orders: ' . (string) $plantCode);
        }

        $equipmentNumber = trim((string) (Arr::get($item, 'equipment_number') ?? ''));
        if ($equipmentNumber !== '') {
            // Ensure equipment exists and is bound to the plant
            $existingEq = Equipment::where('equipment_number', $equipmentNumber)->first();
            if ($existingEq) {
                if ($existingEq->plant_id !== $plant->id) {
                    $existingEq->plant_id = $plant->id;
                    $existingEq->is_active = true;
                    $existingEq->save();
                }
            } else {
                Equipment::updateOrCreate(
                    ['equipment_number' => $equipmentNumber],
                    [
                        'plant_id' => $plant->id,
                        'equipment_group_id' => null,
                        'company_code' => null,
                        'equipment_description' => null,
                        'object_number' => null,
                        'point' => null,
                        'api_created_at' => now(),
                        'is_active' => true,
                    ]
                );
            }
        }

        // Upsert equipment work order record by ims_id
        \App\Models\EquipmentWorkOrder::updateOrCreate(
            ['ims_id' => (string) (Arr::get($item, 'id') ?? '')],
            [
                'reservation' => Arr::get($item, 'reservation'),
                'requirement_type' => Arr::get($item, 'requirement_type'),
                'reservation_status' => Arr::get($item, 'reservation_status'),
                'item_deleted' => Arr::get($item, 'item_deleted'),
                'movement_allowed' => Arr::get($item, 'movement_allowed'),
                'final_issue' => Arr::get($item, 'final_issue'),
                'missing_part' => Arr::get($item, 'missing_part'),
                'material' => Arr::get($item, 'material'),
                'plant_id' => $plant->id,
                'storage_location' => Arr::get($item, 'storage_location'),
                'requirements_date' => Arr::get($item, 'requirements_date'),
                'requirement_quantity' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'requirement_quantity'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'requirement_quantity')) : null,
                'base_unit_of_measure' => Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_ind' => Arr::get($item, 'debit_credit_ind'),
                'quantity_is_fixed' => Arr::get($item, 'quantity_is_fixed'),
                'quantity_withdrawn' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'quantity_withdrawn'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'quantity_withdrawn')) : null,
                'value_withdrawn' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'value_withdrawn'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'value_withdrawn')) : null,
                'currency' => Arr::get($item, 'currency'),
                'qty_in_unit_of_entry' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'qty_in_unit_of_entry'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'qty_in_unit_of_entry')) : null,
                'unit_of_entry' => Arr::get($item, 'unit_of_entry'),
                'movement_type' => Arr::get($item, 'movement_type'),
                'gl_account' => Arr::get($item, 'gl_account'),
                'receiving_plant' => Arr::get($item, 'receiving_plant'),
                'receiving_storage_location' => Arr::get($item, 'receiving_storage_location'),
                'qty_for_avail_check' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'qty_for_avail_check'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'qty_for_avail_check')) : null,
                'goods_recipient' => Arr::get($item, 'goods_recipient'),
                'material_group' => Arr::get($item, 'material_group'),
                'acct_manually' => Arr::get($item, 'acct_manually'),
                'commitment_item_1' => Arr::get($item, 'commitment_item_1'),
                'funds_center' => Arr::get($item, 'funds_center'),
                'start_time' => Arr::get($item, 'start_time'),
                'end_time' => Arr::get($item, 'end_time'),
                'service_duration' => Arr::get($item, 'service_duration'),
                'service_dur_unit' => Arr::get($item, 'service_dur_unit'),
                'api_updated_at' => Arr::get($item, 'updated_at') ? \Carbon\Carbon::parse(Arr::get($item, 'updated_at')) : null,
                'commitment_item_2' => Arr::get($item, 'commitment_item_2'),
                'order_number' => Arr::get($item, 'order_number'),
                'equipment_number' => $equipmentNumber,
            ]
        );
    }

    /**
     * Process equipment material item
     * - Bind `plant` and `production_order` to Equipment (production_order as equipment_number)
     */
    protected function processEquipmentMaterialItem(array $item): void
    {
        // Plant code may be under 'plant'
        $plantCode = Arr::get($item, 'plant');
        if (!$plantCode) {
            throw new Exception('Missing plant in equipment_material item');
        }

        // Skip items outside requested plants when filtering
        if (!empty($this->allowedPlantCodes) && !in_array($plantCode, $this->allowedPlantCodes, true)) {
            return;
        }

        $plant = Plant::where('plant_code', $plantCode)->first();
        if (!$plant) {
            throw new Exception('Plant not found for equipment_material: ' . (string) $plantCode);
        }

        // Use production_order as equipment number per requirement
        $equipmentNumber = (string) (Arr::get($item, 'production_order') ?? '');
        $equipmentNumber = trim($equipmentNumber);

        // Some API may send '-' or empty when not applicable; skip
        if ($equipmentNumber === '' || $equipmentNumber === '-') {
            return;
        }

        // Ensure an Equipment record exists for this production_order under the plant
        $existing = Equipment::where('equipment_number', $equipmentNumber)->first();
        if ($existing) {
            // Ensure plant is set if missing; otherwise leave as-is
            if ($existing->plant_id !== $plant->id) {
                $existing->plant_id = $plant->id;
                $existing->is_active = true;
                $existing->save();
            }
            return;
        }

        Equipment::updateOrCreate(
            ['equipment_number' => $equipmentNumber],
            [
                'plant_id' => $plant->id,
                'equipment_group_id' => null,
                'company_code' => Arr::get($item, 'currency') ? null : null,
                'equipment_description' => null,
                'object_number' => null,
                'point' => null,
                'api_created_at' => now(),
                'is_active' => true,
            ]
        );

        // Upsert equipment material row as well
        \App\Models\EquipmentMaterial::updateOrCreate(
            [
                'ims_id' => (string) (Arr::get($item, 'id') ?? ''),
            ],
            [
                'plant_id' => $plant->id,
                'equipment_number' => $equipmentNumber,
                'material_number' => Arr::get($item, 'material_number') ?? Arr::get($item, 'material'),
                'reservation_number' => Arr::get($item, 'reservation_number') ?? Arr::get($item, 'reservation'),
                'reservation_item' => Arr::get($item, 'reservation_item') ?? Arr::get($item, 'reservation_item'),
                'reservation_type' => Arr::get($item, 'reservation_type'),
                'requirement_type' => Arr::get($item, 'requirement_type') ?? Arr::get($item, 'requirement_type'),
                'reservation_status' => Arr::get($item, 'reservation_status'),
                'deletion_flag' => Arr::get($item, 'deletion_flag') ?? Arr::get($item, 'item_deleted'),
                'goods_receipt_flag' => Arr::get($item, 'goods_receipt_flag') ?? Arr::get($item, 'movement_allowed'),
                'final_issue_flag' => Arr::get($item, 'final_issue_flag') ?? Arr::get($item, 'final_issue'),
                'error_flag' => Arr::get($item, 'error_flag') ?? Arr::get($item, 'missing_part'),
                'storage_location' => Arr::get($item, 'storage_location'),
                'production_supply_area' => Arr::get($item, 'production_supply_area'),
                'batch_number' => Arr::get($item, 'batch_number'),
                'storage_bin' => Arr::get($item, 'storage_bin'),
                'special_stock_indicator' => Arr::get($item, 'special_stock_indicator'),
                'requirement_date' => Arr::get($item, 'requirement_date') ?? Arr::get($item, 'requirements_date'),
                'requirement_qty' => is_numeric(str_replace([','], [''], (string) (Arr::get($item, 'requirement_qty') ?? Arr::get($item, 'requirement_quantity')))) ? (float) str_replace([','], [''], (string) (Arr::get($item, 'requirement_qty') ?? Arr::get($item, 'requirement_quantity'))) : null,
                'unit_of_measure' => Arr::get($item, 'unit_of_measure') ?? Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_indicator' => Arr::get($item, 'debit_credit_indicator') ?? Arr::get($item, 'debit_credit_ind'),
                'issued_qty' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'issued_qty'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'issued_qty')) : null,
                'withdrawn_qty' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'withdrawn_qty'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'withdrawn_qty')) : null,
                'withdrawn_value' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'withdrawn_value'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'withdrawn_value')) : null,
                'currency' => Arr::get($item, 'currency'),
                'entry_qty' => is_numeric(str_replace([','], [''], (string) Arr::get($item, 'entry_qty'))) ? (float) str_replace([','], [''], (string) Arr::get($item, 'entry_qty')) : null,
                'entry_uom' => Arr::get($item, 'entry_uom') ?? Arr::get($item, 'unit_of_entry'),
                'planned_order' => Arr::get($item, 'planned_order'),
                'purchase_requisition' => Arr::get($item, 'purchase_requisition'),
                'purchase_requisition_item' => Arr::get($item, 'purchase_requisition_item'),
                'production_order' => Arr::get($item, 'production_order'),
                'movement_type' => Arr::get($item, 'movement_type'),
                'gl_account' => Arr::get($item, 'gl_account'),
                'receiving_storage_loc' => Arr::get($item, 'receiving_storage_loc') ?? Arr::get($item, 'receiving_storage_location'),
                'receiving_plant' => Arr::get($item, 'receiving_plant'),
                'api_created_at' => Arr::get($item, 'api_created_at') ? \Carbon\Carbon::parse(Arr::get($item, 'api_created_at')) : null,
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
