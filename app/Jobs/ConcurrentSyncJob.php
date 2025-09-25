<?php

namespace App\Jobs;

use App\Services\Sync\ConcurrentApiSyncService;
use App\Models\Plant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ConcurrentSyncJob implements ShouldQueue
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
     * Plant codes to sync (optional, null means all active plants).
     *
     * @var array|null
     */
    protected ?array $plantCodes;

    /**
     * Running time start date.
     *
     * @var string
     */
    protected string $runningTimeStartDate;

    /**
     * Running time end date.
     *
     * @var string
     */
    protected string $runningTimeEndDate;

    /**
     * Work order start date.
     *
     * @var string
     */
    protected string $workOrderStartDate;

    /**
     * Work order end date.
     *
     * @var string
     */
    protected string $workOrderEndDate;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return $this->attempts() * 120; // Exponential backoff: 2min, 4min, 6min
    }

    /**
     * Create a new job instance.
     *
     * @param array|null $plantCodes Plant codes to sync (null = all active plants)
     * @param string|null $runningTimeStartDate Running time start date (defaults to yesterday)
     * @param string|null $runningTimeEndDate Running time end date (defaults to yesterday)
     * @param string|null $workOrderStartDate Work order start date (defaults to first day of previous month)
     * @param string|null $workOrderEndDate Work order end date (defaults to today)
     */
    public function __construct(
        ?array $plantCodes = null,
        ?string $runningTimeStartDate = null,
        ?string $runningTimeEndDate = null,
        ?string $workOrderStartDate = null,
        ?string $workOrderEndDate = null
    ) {
        $this->plantCodes = $plantCodes;
        $this->runningTimeStartDate = $runningTimeStartDate ?? Carbon::yesterday()->toDateString();
        $this->runningTimeEndDate = $runningTimeEndDate ?? Carbon::yesterday()->toDateString();
        $this->workOrderStartDate = $workOrderStartDate ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->workOrderEndDate = $workOrderEndDate ?? Carbon::today()->toDateString();

        // Set job to high priority queue
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();

        Log::info('Starting concurrent sync job', [
            'plant_codes' => $this->plantCodes,
            'running_time_range' => "{$this->runningTimeStartDate} to {$this->runningTimeEndDate}",
            'work_order_range' => "{$this->workOrderStartDate} to {$this->workOrderEndDate}",
            'attempt' => $this->attempts(),
        ]);

        try {
            // Get plant codes if not provided
            $plantCodes = $this->plantCodes ?? Plant::where('is_active', true)->pluck('plant_code')->toArray();

            // Use concurrent sync service
            $syncService = new ConcurrentApiSyncService();
            $results = $syncService->syncAllConcurrently(
                $plantCodes,
                $this->runningTimeStartDate,
                $this->runningTimeEndDate,
                $this->workOrderStartDate,
                $this->workOrderEndDate
            );

            $duration = now()->diffInSeconds($startTime);

            Log::info('Concurrent sync job completed successfully', [
                'duration' => $duration,
                'results' => $results,
                'attempt' => $this->attempts(),
            ]);
        } catch (Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            Log::error('Concurrent sync job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration' => $duration,
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
        Log::critical('Concurrent sync job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'plant_codes' => $this->plantCodes,
            'running_time_range' => "{$this->runningTimeStartDate} to {$this->runningTimeEndDate}",
            'work_order_range' => "{$this->workOrderStartDate} to {$this->workOrderEndDate}",
        ]);
    }
}
