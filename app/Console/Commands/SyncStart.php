<?php

namespace App\Console\Commands;

use App\Jobs\ConcurrentSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:start 
                            {--plants= : Comma-separated list of plant codes to sync (optional, defaults to all active plants)}
                            {--running-time-start= : YYYY-MM-DD start date for running_time API (defaults to 3 days ago)}
                            {--running-time-end= : YYYY-MM-DD end date for running_time API (defaults to now)}
                            {--work-order-start= : YYYY-MM-DD start date for work_orders, equipment_work_orders, equipment_material APIs (defaults to 3 days ago)}
                            {--work-order-end= : YYYY-MM-DD end date for work_orders, equipment_work_orders, equipment_material APIs (defaults to now)}
                            {--types= : Comma-separated list of data types to sync (defaults to ALL 5: equipment,running_time,work_orders,equipment_work_orders,equipment_material)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start sequential synchronization for all 5 APIs (equipment → running_time → work_orders → equipment_work_orders → equipment_material)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🚀 Starting sequential synchronization for all 5 APIs...");
        $this->info("Mode: Sequential HTTP Requests (respecting dependencies)");
        $this->info("APIs: equipment → running_time → work_orders → equipment_work_orders → equipment_material");

        $startTime = now();

        try {
            // Get plant codes
            $plantCodes = $this->getPlantCodes();

            // Get date ranges
            $runningTimeStart = $this->option('running-time-start') ?? Carbon::now()->subDays(3)->toDateString();
            $runningTimeEnd = $this->option('running-time-end') ?? Carbon::now()->toDateString();
            $workOrderStart = $this->option('work-order-start') ?? Carbon::now()->subDays(3)->toDateString();
            $workOrderEnd = $this->option('work-order-end') ?? Carbon::now()->toDateString();

            $this->info("Plants: " . count($plantCodes) . " (" . implode(', ', array_slice($plantCodes, 0, 5)) . (count($plantCodes) > 5 ? '...' : '') . ")");
            $this->info("Running Time API: {$runningTimeStart} to {$runningTimeEnd}");
            $this->info("Work Orders/Equipment Work Orders/Equipment Material APIs: {$workOrderStart} to {$workOrderEnd}");

            // Parse selected types (optional - defaults to all 5 APIs)
            $typesOption = $this->option('types');
            $types = null;
            if ($typesOption) {
                $types = collect(explode(',', $typesOption))
                    ->map(fn($t) => trim($t))
                    ->filter()
                    ->values()
                    ->all();
                if (empty($types)) {
                    $types = null;
                }
                $this->info("Selected APIs: " . implode(' → ', $types));
            } else {
                $this->info("Selected APIs: All 5 APIs (equipment → running_time → work_orders → equipment_work_orders → equipment_material)");
            }

            // Dispatch the sequential sync job
            ConcurrentSyncJob::dispatch(
                $plantCodes,
                $runningTimeStart,
                $runningTimeEnd,
                $workOrderStart,
                $workOrderEnd,
                $types
            )->onQueue('high');

            $duration = now()->diffInSeconds($startTime);

            $this->info("✅ Sync job dispatched successfully in {$duration} seconds");
            $this->info("💡 Process the job with: php artisan queue:work --queue=high");
            $this->info("📊 The job will sync all 5 APIs in dependency order");

            Log::info('Master sync command completed successfully', [
                'duration' => $duration,
                'plants' => $plantCodes,
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);
            $this->error("❌ Synchronization failed: " . $e->getMessage());

            Log::error('Master sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Get plant codes to sync
     */
    private function getPlantCodes(): array
    {
        $plantsOption = $this->option('plants');

        if ($plantsOption) {
            // Use specified plants
            return array_map('trim', explode(',', $plantsOption));
        }

        // Use all active plants
        return \App\Models\Plant::where('is_active', true)
            ->pluck('plant_code')
            ->toArray();
    }
}
