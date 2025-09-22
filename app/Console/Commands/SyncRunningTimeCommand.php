<?php

namespace App\Console\Commands;

use App\Jobs\SyncRunningTimeJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncRunningTimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:running-time 
                            {--date= : Specific date to sync (YYYY-MM-DD format, defaults to yesterday)} 
                            {--force : Force sync even if another sync is running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize equipment running time data from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');
        $dateString = $date ?? Carbon::yesterday()->toDateString();

        $this->info("Starting running time synchronization for date: {$dateString}");

        try {
            // Validate date format if provided
            if ($date) {
                try {
                    Carbon::createFromFormat('Y-m-d', $date);
                } catch (\Exception $e) {
                    $this->error('Invalid date format. Please use YYYY-MM-DD format.');
                    return \Symfony\Component\Console\Command\Command::FAILURE;
                }
            }

            // Check if there's already a running sync job for this date (unless forced)
            if (!$this->option('force')) {
                $runningSyncLog = \App\Models\ApiSyncLog::where('sync_type', 'running_time')
                    ->where('status', 'running')
                    ->where('created_at', '>=', now()->subHours(2)) // Check for jobs started in last 2 hours
                    ->first();

                if ($runningSyncLog) {
                    $this->warn('Running time synchronization is already running. Use --force to override.');
                    return \Symfony\Component\Console\Command\Command::FAILURE;
                }
            }

            // Dispatch the sync job
            SyncRunningTimeJob::dispatch($date);

            $this->info("Running time synchronization job for {$dateString} has been dispatched to the queue.");

            Log::info('Running time synchronization command executed', [
                'date' => $dateString,
                'forced' => $this->option('force'),
                'user' => $this->getUser(),
            ]);

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to dispatch running time synchronization job: ' . $e->getMessage());

            Log::error('Running time synchronization command failed', [
                'date' => $dateString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }
    }

    /**
     * Get the current user context (if available).
     *
     * @return string
     */
    protected function getUser(): string
    {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $user = posix_getpwuid(posix_geteuid());
            return $user['name'] ?? 'unknown';
        }

        return 'system';
    }
}
