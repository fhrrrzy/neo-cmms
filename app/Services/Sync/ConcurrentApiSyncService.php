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
use App\Services\Sync\Processors\EquipmentWorkOrderProcessor;
use App\Services\Sync\Processors\EquipmentMaterialProcessor;
use App\Services\Sync\Processors\RunningTimeProcessor;
use App\Services\Sync\Processors\WorkOrderProcessor;
use App\Services\Sync\Processors\DailyPlantDataProcessor;
use App\Services\Sync\Fetchers\EquipmentFetcher;
use App\Services\Sync\Fetchers\RunningTimeFetcher;
use App\Services\Sync\Fetchers\WorkOrderFetcher;
use App\Services\Sync\Fetchers\EquipmentWorkOrderFetcher;
use App\Services\Sync\Fetchers\EquipmentMaterialFetcher;
use App\Services\Sync\Fetchers\DailyPlantDataFetcher;
use App\Models\DailyPlantData;

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
        $this->info("Order: equipment → work_orders → running_time → equipment_work_orders → equipment_materials → daily_plant_data");
        $this->info("Types parameter: " . json_encode($types));

        $startTime = now();
        $results = [];

        try {
            // Define the sync order (respecting dependencies)
            // equipment -> work_orders -> running_time -> equipment_work_orders -> equipment_materials -> daily_plant_data
            $syncOrder = ['equipment', 'work_orders', 'running_time', 'equipment_work_orders', 'equipment_materials', 'daily_plant_data'];
            $selectedTypes = $types ?? $syncOrder;

            $this->info("Types in sync order: " . json_encode($syncOrder));
            $this->info("Selected types: " . json_encode($selectedTypes));

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
        try {
            // Fetch data using the appropriate fetcher
            $items = $this->fetchApiData($apiType, $plantCodes, $runningTimeStartDate, $runningTimeEndDate, $workOrderStartDate, $workOrderEndDate);

            $this->info("Total {$apiType} items: " . count($items));

            if (!empty($items)) {
                return $this->processApiData($apiType, $items, $runningTimeStartDate, $runningTimeEndDate);
            } else {
                return ['processed' => 0, 'success' => 0, 'failed' => 0];
            }
        } catch (Exception $e) {
            $this->error("❌ {$apiType}: " . $e->getMessage());
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch API data using the appropriate fetcher
     */
    protected function fetchApiData(string $apiType, array $plantCodes, string $runningTimeStartDate, string $runningTimeEndDate, string $workOrderStartDate, string $workOrderEndDate): array
    {
        switch ($apiType) {
            case 'equipment':
                return (new EquipmentFetcher())->fetch($plantCodes);

            case 'running_time':
                return (new RunningTimeFetcher())->fetch($plantCodes, $runningTimeStartDate, $runningTimeEndDate);

            case 'work_orders':
                return (new WorkOrderFetcher())->fetch($plantCodes, $workOrderStartDate, $workOrderEndDate);

            case 'equipment_work_orders':
                return (new EquipmentWorkOrderFetcher())->fetch($plantCodes, $workOrderStartDate, $workOrderEndDate);

            case 'equipment_materials':
                return (new EquipmentMaterialFetcher())->fetch($plantCodes, $workOrderStartDate, $workOrderEndDate);

            case 'daily_plant_data':
                return (new DailyPlantDataFetcher())->fetch($plantCodes, $runningTimeStartDate, $runningTimeEndDate);

            default:
                throw new Exception("Unknown API type: {$apiType}");
        }
    }


    /**
     * Process data for a specific API type
     */
    protected function processApiData(string $apiType, array $items, ?string $startDate = null, ?string $endDate = null): array
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
            DB::transaction(function () use ($items, $apiType, $startDate, $endDate, &$processed, &$success, &$failed) {
                $processed = count($items);
                try {
                    switch ($apiType) {
                        case 'equipment':
                            (new EquipmentProcessor())->processBatch($items);
                            break;
                        case 'equipment_work_orders':
                            (new EquipmentWorkOrderProcessor())->processBatch($items, $this->allowedPlantCodes);
                            break;
                        case 'equipment_materials':
                            (new EquipmentMaterialProcessor())->processBatch($items, $this->allowedPlantCodes);
                            break;
                        case 'running_time':
                            (new RunningTimeProcessor())->processBatch($items, $this->allowedPlantCodes);
                            break;
                        case 'work_orders':
                            (new WorkOrderProcessor())->processBatch($items);
                            break;
                        case 'daily_plant_data':
                            (new DailyPlantDataProcessor())->processBatch($items, $startDate ?? now()->toDateString());
                            break;
                    }
                    $success = $processed;
                    $failed = 0;
                } catch (\Throwable $e) {
                    $success = 0;
                    $failed = $processed;
                    \Illuminate\Support\Facades\Log::error('Sync batch failed', [
                        'api_type' => $apiType,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'items_count' => $processed,
                    ]);
                    // Re-throw to trigger transaction rollback
                    throw $e;
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
