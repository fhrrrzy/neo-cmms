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
                            {--running-time-start= : YYYY-MM-DD start date for running time (defaults to yesterday)}
                            {--running-time-end= : YYYY-MM-DD end date for running time (defaults to yesterday)}
                            {--work-order-start= : YYYY-MM-DD start date for work orders (defaults to first day of previous month)}
                            {--work-order-end= : YYYY-MM-DD end date for work orders (defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start concurrent synchronization for all APIs (equipment, running time, work orders)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🚀 Starting concurrent synchronization...");
        $this->info("Mode: Concurrent HTTP Requests (Http::pool)");

        $startTime = now();

        try {
            // Get plant codes
            $plantCodes = $this->getPlantCodes();

            // Get date ranges
            $runningTimeStart = $this->option('running-time-start') ?? Carbon::yesterday()->toDateString();
            $runningTimeEnd = $this->option('running-time-end') ?? Carbon::yesterday()->toDateString();
            $workOrderStart = $this->option('work-order-start') ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $workOrderEnd = $this->option('work-order-end') ?? Carbon::today()->toDateString();

            $this->info("Plants: " . count($plantCodes) . " (" . implode(', ', array_slice($plantCodes, 0, 5)) . (count($plantCodes) > 5 ? '...' : '') . ")");
            $this->info("Running Time: {$runningTimeStart} to {$runningTimeEnd}");
            $this->info("Work Orders: {$workOrderStart} to {$workOrderEnd}");

            // Dispatch the concurrent sync job
            ConcurrentSyncJob::dispatch(
                $plantCodes,
                $runningTimeStart,
                $runningTimeEnd,
                $workOrderStart,
                $workOrderEnd
            )->onQueue('high');

            $duration = now()->diffInSeconds($startTime);

            $this->info("✅ Sync job dispatched successfully in {$duration} seconds");
            $this->info("💡 Process the job with: php artisan queue:work --queue=high");

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
