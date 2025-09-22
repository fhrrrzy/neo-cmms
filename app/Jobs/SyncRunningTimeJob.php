<?php

namespace App\Jobs;

use App\Services\Sync\RunningTimeSyncService;
use App\Models\ApiSyncLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SyncRunningTimeJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 3600; // 60 minutes

    /**
     * The date to sync running time data for.
     *
     * @var string|null
     */
    protected ?string $syncDate;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return $this->attempts() * 90; // Exponential backoff: 1.5min, 3min, 4.5min
    }

    /**
     * Create a new job instance.
     *
     * @param string|null $date Date to sync (Y-m-d format). Defaults to yesterday.
     */
    public function __construct(?string $date = null)
    {
        $this->syncDate = $date;
        
        // Set job to high priority queue
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(RunningTimeSyncService $syncService): void
    {
        $syncDateString = $this->syncDate ?? Carbon::yesterday()->toDateString();
        
        Log::info('Starting running time synchronization job', [
            'sync_date' => $syncDateString,
        ]);

        try {
            $syncLog = $syncService->syncRunningTime($this->syncDate);

            // Log successful completion
            Log::info('Running time synchronization completed successfully', [
                'sync_log_id' => $syncLog->id,
                'sync_date' => $syncDateString,
                'records_processed' => $syncLog->records_processed,
                'records_success' => $syncLog->records_success,
                'records_failed' => $syncLog->records_failed,
                'success_rate' => $syncLog->success_rate ?? 0,
                'duration' => $syncLog->duration ?? 0,
            ]);

            // Check if there were any failures and send warning if needed
            if ($syncLog->records_failed > 0) {
                $failureRate = ($syncLog->records_failed / $syncLog->records_processed) * 100;
                if ($failureRate > 10) { // More than 10% failure rate
                    Log::warning('High failure rate detected in running time sync', [
                        'sync_log_id' => $syncLog->id,
                        'sync_date' => $syncDateString,
                        'failure_rate' => $failureRate,
                    ]);
                }
            }

            // Check for equipment without running time data
            $this->checkForMissingRunningTimeData($syncDateString);

        } catch (Exception $e) {
            Log::error('Running time synchronization job failed', [
                'sync_date' => $syncDateString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        $syncDateString = $this->syncDate ?? Carbon::yesterday()->toDateString();
        
        Log::critical('Running time synchronization job failed permanently', [
            'sync_date' => $syncDateString,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Create a failed sync log entry
        ApiSyncLog::create([
            'sync_type' => ApiSyncLog::SYNC_TYPE_RUNNING_TIME,
            'status' => ApiSyncLog::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
            'sync_started_at' => now(),
            'sync_completed_at' => now(),
            'records_processed' => 0,
            'records_success' => 0,
            'records_failed' => 0,
        ]);
    }

    /**
     * Check for equipment without running time data for the sync date.
     */
    protected function checkForMissingRunningTimeData(string $syncDate): void
    {
        try {
            $equipmentWithoutData = \App\Models\Equipment::active()
                ->whereDoesntHave('runningTimes', function ($query) use ($syncDate) {
                    $query->where('date', $syncDate);
                })
                ->count();

            if ($equipmentWithoutData > 0) {
                Log::warning('Equipment found without running time data', [
                    'sync_date' => $syncDate,
                    'equipment_count' => $equipmentWithoutData,
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to check for missing running time data', [
                'sync_date' => $syncDate,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['sync', 'running-time', 'daily', 'date:' . ($this->syncDate ?? 'yesterday')];
    }

    /**
     * Get unique ID for the job to prevent duplicate jobs.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        $date = $this->syncDate ?? Carbon::yesterday()->toDateString();
        return 'sync-running-time-' . $date;
    }
}
