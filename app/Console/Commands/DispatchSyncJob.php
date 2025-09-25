<?php

namespace App\Console\Commands;

use App\Jobs\ConcurrentSyncJob;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DispatchSyncJob extends Command
{
    protected $signature = 'sync:dispatch 
                            {--plants= : Comma-separated list of plant codes to sync (optional, defaults to all active plants)}
                            {--running-time-start= : YYYY-MM-DD start date for running time (defaults to yesterday)}
                            {--running-time-end= : YYYY-MM-DD end date for running time (defaults to yesterday)}
                            {--work-order-start= : YYYY-MM-DD start date for work orders (defaults to first day of previous month)}
                            {--work-order-end= : YYYY-MM-DD end date for work orders (defaults to today)}
                            {--queue=high : Queue name to dispatch to (default: high)}';

    protected $description = 'Dispatch concurrent sync job to queue';

    public function handle(): int
    {
        $plantsOption = $this->option('plants');
        $plantCodes = $plantsOption ? array_map('trim', explode(',', $plantsOption)) : null;

        $runningTimeStart = $this->option('running-time-start') ?? Carbon::yesterday()->toDateString();
        $runningTimeEnd = $this->option('running-time-end') ?? Carbon::yesterday()->toDateString();
        $workOrderStart = $this->option('work-order-start') ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $workOrderEnd = $this->option('work-order-end') ?? Carbon::today()->toDateString();
        $queue = $this->option('queue');

        $this->info("🚀 Dispatching concurrent sync job to '{$queue}' queue...");

        if ($plantCodes) {
            $this->info("Plants: " . implode(', ', $plantCodes));
        } else {
            $this->info("Plants: All active plants");
        }

        $this->info("Running Time: {$runningTimeStart} to {$runningTimeEnd}");
        $this->info("Work Orders: {$workOrderStart} to {$workOrderEnd}");

        try {
            ConcurrentSyncJob::dispatch(
                $plantCodes,
                $runningTimeStart,
                $runningTimeEnd,
                $workOrderStart,
                $workOrderEnd
            )->onQueue($queue);

            $this->info("✅ Sync job dispatched successfully!");
            $this->info("💡 Monitor the job with: php artisan queue:work --queue={$queue}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to dispatch sync job: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
