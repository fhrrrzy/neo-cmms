<?php

namespace App\Services\Sync;

use App\Models\Plant;
use App\Models\ApiSyncLog;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\Station;
use App\Models\RunningTime;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;
use App\Services\Sync\Processors\EquipmentProcessor;
use App\Services\Sync\Processors\EquipmentWorkOrderMaterialProcessor;
use App\Services\Sync\Processors\RunningTimeProcessor;
use App\Services\Sync\Processors\WorkOrderProcessor;

class ConcurrentApiSyncService
{
    protected int $timeoutSeconds;
    protected array $allowedPlantCodes = [];

    public function __construct()
    {
        $this->timeoutSeconds = config('ims.timeout', 300);
    }

    /**
     * Sync all data types sequentially in dependency order
     * Order: equipment -> running_time -> work_orders -> equipment_work_order_materials
     */
    public function syncAllSequentially(array $plantCodes = [], ?string $runningTimeStartDate = null, ?string $runningTimeEndDate = null, ?string $workOrderStartDate = null, ?string $workOrderEndDate = null, ?array $types = null): array
    {
        // Get plant codes if not provided
        if (empty($plantCodes)) {
            $plantCodes = Plant::where('is_active', true)->pluck('plant_code')->toArray();
        }

        // Store allowed plant codes for filtering
        $this->allowedPlantCodes = $plantCodes;

        // Set default dates (running time: 3 days ago to now; work orders: 3 days ago to now)
        $runningTimeStartDate = $runningTimeStartDate ?? Carbon::now()->subDays(3)->toDateString();
        $runningTimeEndDate = $runningTimeEndDate ?? Carbon::now()->toDateString();
        $workOrderStartDate = $workOrderStartDate ?? Carbon::now()->subDays(3)->toDateString();
        $workOrderEndDate = $workOrderEndDate ?? Carbon::now()->toDateString();

        $this->info("🚀 Starting sequential sync for " . count($plantCodes) . " plants");
        $this->info("APIs: Equipment, Running Time ({$runningTimeStartDate} to {$runningTimeEndDate}), Work Orders ({$workOrderStartDate} to {$workOrderEndDate})");
        $this->info("Order: equipment → work_orders → running_time → equipment_work_order_materials");

        $startTime = now();
        $results = [];

        try {
            // Define the sync order (respecting dependencies)
            // equipment -> work_orders -> running_time -> equipment_work_order_materials (combined)
            $syncOrder = ['equipment', 'work_orders', 'running_time', 'equipment_work_order_materials'];
            $selectedTypes = $types ?? $syncOrder;

            foreach ($syncOrder as $apiType) {
                // Skip if not in selected types
                if (!in_array($apiType, $selectedTypes)) {
                    $this->info("⏭️ Skipping {$apiType} (not selected)");
                    $results[$apiType] = ['processed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => true];
                    continue;
                }

                $this->info("📡 Syncing {$apiType}...");
                $stepStartTime = now();

                // Make API request and process immediately
                $result = $this->syncApiType($apiType, $plantCodes, $runningTimeStartDate, $runningTimeEndDate, $workOrderStartDate, $workOrderEndDate);
                $results[$apiType] = $result;

                $stepDuration = now()->diffInSeconds($stepStartTime);
                $this->info("✅ {$apiType}: processed={$result['processed']}, success={$result['success']}, failed={$result['failed']} (took {$stepDuration}s)");
            }

            $duration = now()->diffInSeconds($startTime);
            $this->info("✅ Sequential sync completed in {$duration} seconds");

            return $results;
        } catch (Exception $e) {
            $duration = now()->diffInSeconds($startTime);
            $this->error("❌ Sequential sync failed after {$duration} seconds: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync a single API type
     */
    protected function syncApiType(string $apiType, array $plantCodes, string $runningTimeStartDate, string $runningTimeEndDate, string $workOrderStartDate, string $workOrderEndDate): array
    {
        $baseUrl = rtrim(config('ims.base_url'), '/');
        $token = config('ims.token');

        try {
            // Make API request based on type
            switch ($apiType) {
                case 'equipment':
                    // Process plants in batches to avoid API limits
                    $batchSize = 5; // Process 5 plants at a time
                    $plantBatches = array_chunk($plantCodes, $batchSize);
                    $allItems = [];

                    foreach ($plantBatches as $batchIndex => $plantBatch) {
                        $url = $baseUrl . '/equipments';
                        $this->info("GET {$url} (batch " . ($batchIndex + 1) . "/" . count($plantBatches) . ", plants: " . implode(', ', $plantBatch) . ")");

                        $response = Http::withHeaders([
                            'Authorization' => str_replace('Bearer ', '', $token),
                            'Content-Type' => 'application/json'
                        ])
                            ->timeout($this->timeoutSeconds)
                            ->send('GET', $url, [
                                'json' => ['plant' => array_values($plantBatch)],
                            ]);

                        if ($response->successful()) {
                            $data = $response->json() ?? [];
                            $items = $data['data'] ?? $data;
                            if (!empty($items) && is_array($items)) {
                                $allItems = array_merge($allItems, $items);
                                $this->info("Batch " . ($batchIndex + 1) . " returned " . count($items) . " items");
                            }
                        }
                    }

                    $this->info("Total equipment items from all batches: " . count($allItems));

                    if (!empty($allItems)) {
                        return $this->processApiData($apiType, $allItems);
                    } else {
                        return ['processed' => 0, 'success' => 0, 'failed' => 0];
                    }

                case 'running_time':
                    // Process plants in batches to avoid API limits
                    $batchSize = 5; // Process 5 plants at a time
                    $plantBatches = array_chunk($plantCodes, $batchSize);
                    $allItems = [];

                    foreach ($plantBatches as $batchIndex => $plantBatch) {
                        $url = $baseUrl . '/equipments/jam-jalan?start_date=' . urlencode($runningTimeStartDate) . '&end_date=' . urlencode($runningTimeEndDate);
                        $this->info("GET {$url} (batch " . ($batchIndex + 1) . "/" . count($plantBatches) . ", plants: " . implode(', ', $plantBatch) . ")");

                        $response = Http::withHeaders([
                            'Authorization' => str_replace('Bearer ', '', $token),
                            'Content-Type' => 'application/json'
                        ])
                            ->timeout($this->timeoutSeconds)
                            ->send('GET', $url, [
                                'json' => ['plant' => array_values($plantBatch)],
                            ]);

                        if ($response->successful()) {
                            $data = $response->json() ?? [];
                            $items = $data['data'] ?? $data;
                            if (!empty($items) && is_array($items)) {
                                $allItems = array_merge($allItems, $items);
                                $this->info("Batch " . ($batchIndex + 1) . " returned " . count($items) . " items");
                            }
                        }
                    }

                    $this->info("Total running_time items from all batches: " . count($allItems));

                    if (!empty($allItems)) {
                        return $this->processApiData($apiType, $allItems);
                    } else {
                        return ['processed' => 0, 'success' => 0, 'failed' => 0];
                    }

                case 'work_orders':
                    // Process plants in batches to avoid API limits
                    $batchSize = 5; // Process 5 plants at a time
                    $plantBatches = array_chunk($plantCodes, $batchSize);
                    $allItems = [];

                    foreach ($plantBatches as $batchIndex => $plantBatch) {
                        $url = $baseUrl . '/work-order?start_date=' . urlencode($workOrderStartDate) . '&end_date=' . urlencode($workOrderEndDate);
                        $this->info("GET {$url} (batch " . ($batchIndex + 1) . "/" . count($plantBatches) . ", plants: " . implode(', ', $plantBatch) . ")");

                        $response = Http::withHeaders([
                            'Authorization' => str_replace('Bearer ', '', $token),
                            'Content-Type' => 'application/json'
                        ])
                            ->timeout($this->timeoutSeconds)
                            ->send('GET', $url, [
                                'json' => ['plant' => array_values($plantBatch)],
                            ]);

                        if ($response->successful()) {
                            $data = $response->json() ?? [];
                            $items = $data['data'] ?? $data;
                            if (!empty($items) && is_array($items)) {
                                $allItems = array_merge($allItems, $items);
                                $this->info("Batch " . ($batchIndex + 1) . " returned " . count($items) . " items");
                            }
                        }
                    }

                    $this->info("Total work_orders items from all batches: " . count($allItems));

                    if (!empty($allItems)) {
                        return $this->processApiData($apiType, $allItems);
                    } else {
                        return ['processed' => 0, 'success' => 0, 'failed' => 0];
                    }

                case 'equipment_work_order_materials':
                    // Process both equipment_work_orders and equipment_material APIs and combine the data
                    $batchSize = 5; // Process 5 plants at a time
                    $plantBatches = array_chunk($plantCodes, $batchSize);
                    $allWorkOrderItems = [];
                    $allMaterialItems = [];

                    // Fetch equipment_work_orders data
                    foreach ($plantBatches as $batchIndex => $plantBatch) {
                        $url = $baseUrl . '/equipments/work-order?start_date=' . urlencode($workOrderStartDate) . '&end_date=' . urlencode($workOrderEndDate);
                        $requestBody = ['plant' => array_values($plantBatch)];
                        $this->info("GET {$url} (batch " . ($batchIndex + 1) . "/" . count($plantBatches) . ", plants: " . implode(', ', $plantBatch) . ")");

                        $response = Http::withHeaders([
                            'Authorization' => str_replace('Bearer ', '', $token),
                            'Content-Type' => 'application/json'
                        ])
                            ->timeout($this->timeoutSeconds)
                            ->send('GET', $url, [
                                'json' => $requestBody,
                            ]);

                        if ($response->successful()) {
                            $data = $response->json() ?? [];
                            $items = $data['data'] ?? $data;
                            if (!empty($items) && is_array($items)) {
                                $allWorkOrderItems = array_merge($allWorkOrderItems, $items);
                                $this->info("Work orders batch " . ($batchIndex + 1) . " returned " . count($items) . " items");
                            }
                        }
                    }

                    // Fetch equipment_material data
                    foreach ($plantBatches as $batchIndex => $plantBatch) {
                        $url = $baseUrl . '/equipments/material?start_date=' . urlencode($workOrderStartDate) . '&end_date=' . urlencode($workOrderEndDate);
                        $this->info("GET {$url} (batch " . ($batchIndex + 1) . "/" . count($plantBatches) . ", plants: " . implode(', ', $plantBatch) . ")");

                        $response = Http::withHeaders([
                            'Authorization' => str_replace('Bearer ', '', $token),
                            'Content-Type' => 'application/json'
                        ])
                            ->timeout($this->timeoutSeconds)
                            ->send('GET', $url, [
                                'json' => ['plant' => array_values($plantBatch)],
                            ]);

                        if ($response->successful()) {
                            $data = $response->json() ?? [];
                            $items = $data['data'] ?? $data;
                            if (!empty($items) && is_array($items)) {
                                $allMaterialItems = array_merge($allMaterialItems, $items);
                                $this->info("Materials batch " . ($batchIndex + 1) . " returned " . count($items) . " items");
                            }
                        }
                    }

                    $this->info("Total equipment_work_orders items: " . count($allWorkOrderItems));
                    $this->info("Total equipment_material items: " . count($allMaterialItems));

                    // Process both types with the unified processor
                    $workOrderResults = !empty($allWorkOrderItems) ? $this->processApiData('equipment_work_order_materials', $allWorkOrderItems) : ['processed' => 0, 'success' => 0, 'failed' => 0];
                    $materialResults = !empty($allMaterialItems) ? $this->processApiData('equipment_work_order_materials', $allMaterialItems) : ['processed' => 0, 'success' => 0, 'failed' => 0];

                    // Combine results
                    return [
                        'processed' => $workOrderResults['processed'] + $materialResults['processed'],
                        'success' => $workOrderResults['success'] + $materialResults['success'],
                        'failed' => $workOrderResults['failed'] + $materialResults['failed'],
                    ];

                default:
                    throw new Exception("Unknown API type: {$apiType}");
            }

            // Process response (skip for running_time as it's already handled above)
            if ($apiType !== 'running_time') {
                if ($response->successful()) {
                    $data = $response->json() ?? [];
                    $items = $data['data'] ?? $data;

                    Log::info("API {$apiType} response structure", [
                        'has_data_key' => isset($data['data']),
                        'data_type' => gettype($items),
                        'items_count' => is_array($items) ? count($items) : 'not_array',
                        'sample_item' => is_array($items) && !empty($items) ? array_keys($items[0]) : 'no_items',
                        'full_response_keys' => array_keys($data),
                        'response_message' => $data['message'] ?? 'no_message'
                    ]);

                    if (!empty($items) && is_array($items)) {
                        return $this->processApiData($apiType, $items);
                    } else {
                        // Log a short preview of the raw body to aid debugging when API returns no items
                        try {
                            $raw = (string) $response->body();
                            $preview = substr($raw, 0, 500);
                            Log::info("{$apiType} raw response preview (500 chars): " . $preview);
                        } catch (\Throwable $t) {
                            // ignore logging errors
                        }
                        $this->info("ℹ️ {$apiType}: No data returned");
                        return ['processed' => 0, 'success' => 0, 'failed' => 0];
                    }
                } else {
                    throw new Exception("HTTP {$response->status()}: {$response->body()}");
                }
            }
        } catch (Exception $e) {
            $this->error("❌ {$apiType}: " . $e->getMessage());
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
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
                            case 'equipment_work_order_materials':
                                (new EquipmentWorkOrderMaterialProcessor())->process($item, $this->allowedPlantCodes, 'equipment_work_order_materials');
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
                        \Illuminate\Support\Facades\Log::error('Sync item failed', [
                            'api_type' => $apiType,
                            'error' => $e->getMessage(),
                            'item_preview' => is_array($item) ? array_intersect_key($item, array_flip(['id', 'reservation_number', 'material_number', 'plant', 'created_at'])) : $item,
                        ]);
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
