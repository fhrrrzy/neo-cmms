<?php

namespace App\Jobs;

use App\Services\Sync\EquipmentSyncService;
use App\Models\ApiSyncLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncEquipmentJob implements ShouldQueue
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
    public $timeout = 1800; // 30 minutes

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return $this->attempts() * 60; // Exponential backoff: 1min, 2min, 3min
    }

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set job to high priority queue
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(EquipmentSyncService $syncService): void
    {
        Log::info('Starting equipment synchronization job');

        try {
            $syncLog = $syncService->syncEquipment();

            // Log successful completion
            Log::info('Equipment synchronization completed successfully', [
                'sync_log_id' => $syncLog->id,
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
                    Log::warning('High failure rate detected in equipment sync', [
                        'sync_log_id' => $syncLog->id,
                        'failure_rate' => $failureRate,
                    ]);
                }
            }

        } catch (Exception $e) {
            Log::error('Equipment synchronization job failed', [
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
        Log::critical('Equipment synchronization job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Create a failed sync log entry
        ApiSyncLog::create([
            'sync_type' => ApiSyncLog::SYNC_TYPE_EQUIPMENT,
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
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['sync', 'equipment', 'daily'];
    }
}
