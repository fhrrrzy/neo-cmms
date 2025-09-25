<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
            $exitCode = Artisan::call('sync:all-concurrent', [
                '--plants' => $this->option('plants'),
                '--running-time-start' => $this->option('running-time-start') ?? Carbon::yesterday()->toDateString(),
                '--running-time-end' => $this->option('running-time-end') ?? Carbon::yesterday()->toDateString(),
                '--work-order-start' => $this->option('work-order-start') ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                '--work-order-end' => $this->option('work-order-end') ?? Carbon::today()->toDateString(),
            ]);

            $output = Artisan::output();
            $this->line($output);

            $duration = now()->diffInSeconds($startTime);

            if ($exitCode === 0) {
                $this->info("✅ Synchronization completed successfully in {$duration} seconds");

                Log::info('Master sync command completed successfully', [
                    'duration' => $duration,
                    'plants' => $this->option('plants'),
                ]);

                return self::SUCCESS;
            } else {
                $this->error("❌ Synchronization failed after {$duration} seconds");
                return self::FAILURE;
            }
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
}
