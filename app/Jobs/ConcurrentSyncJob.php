<?php

namespace App\Jobs;

use App\Services\Sync\ConcurrentApiSyncService;
use App\Models\Plant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use App\Models\User;
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
     * Selected types to sync (null = all).
     * @var array|null
     */
    protected ?array $types = null;

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
     * @param string|null $runningTimeStartDate Running time start date (defaults to 3 days ago)
     * @param string|null $runningTimeEndDate Running time end date (defaults to now)
     * @param string|null $workOrderStartDate Work order start date (defaults to 3 days ago)
     * @param string|null $workOrderEndDate Work order end date (defaults to now)
     */
    public function __construct(
        ?array $plantCodes = null,
        ?string $runningTimeStartDate = null,
        ?string $runningTimeEndDate = null,
        ?string $workOrderStartDate = null,
        ?string $workOrderEndDate = null,
        ?array $types = null
    ) {
        $this->plantCodes = $plantCodes;
        $this->runningTimeStartDate = $runningTimeStartDate ?? Carbon::now()->subDays(3)->toDateString();
        $this->runningTimeEndDate = $runningTimeEndDate ?? Carbon::now()->toDateString();
        $this->workOrderStartDate = $workOrderStartDate ?? Carbon::now()->subDays(3)->toDateString();
        $this->workOrderEndDate = $workOrderEndDate ?? Carbon::now()->toDateString();
        $this->types = $types;

        // Set job to high priority queue
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();

        Log::info('Starting sequential sync job', [
            'plant_codes' => $this->plantCodes,
            'running_time_range' => "{$this->runningTimeStartDate} to {$this->runningTimeEndDate}",
            'work_order_range' => "{$this->workOrderStartDate} to {$this->workOrderEndDate}",
            'attempt' => $this->attempts(),
        ]);

        try {
            // Get plant codes if not provided
            $plantCodes = $this->plantCodes ?? Plant::where('is_active', true)->pluck('plant_code')->toArray();

            // Use sequential sync service
            $syncService = new ConcurrentApiSyncService();
            $results = $syncService->syncAllSequentially(
                $plantCodes,
                $this->runningTimeStartDate,
                $this->runningTimeEndDate,
                $this->workOrderStartDate,
                $this->workOrderEndDate,
                $this->types
            );

            $duration = now()->diffInSeconds($startTime);

            Log::info('Sequential sync job completed successfully', [
                'duration' => $duration,
                'results' => $results,
                'attempt' => $this->attempts(),
            ]);

            // Notify superadmins via Filament database notifications
            $recipients = User::superadmin()->get();

            // Detailed notification for tracking - only show synced types
            $syncedResults = [];
            if ($this->types === null) {
                // All types synced
                $syncedResults[] = 'Equipment: ' . ($results['equipment']['success'] ?? 0);
                $syncedResults[] = 'Equipment Work Order Materials: ' . ($results['equipment_work_order_materials']['success'] ?? 0);
                $syncedResults[] = 'Running Time: ' . ($results['running_time']['success'] ?? 0);
                $syncedResults[] = 'Work Orders: ' . ($results['work_orders']['success'] ?? 0);
            } else {
                // Only show selected types
                foreach ($this->types as $type) {
                    $typeLabel = match ($type) {
                        'equipment' => 'Equipment',
                        'equipment_work_order_materials' => 'Equipment Work Order Materials',
                        'running_time' => 'Running Time',
                        'work_orders' => 'Work Orders',
                        'daily_plant_data' => 'Daily Plant Data',
                        default => ucfirst($type)
                    };
                    $syncedResults[] = $typeLabel . ': ' . ($results[$type]['success'] ?? 0);
                }
            }

            FilamentNotification::make()
                ->title('Sequential API Sync Completed Successfully')
                ->body('Results: ' . implode(', ', $syncedResults) . ' | Duration: ' . $duration . 's | Plants: ' . count($plantCodes))
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->sendToDatabase($recipients);

            // Summary notification for quick awareness
            $syncTypesText = $this->types === null ? 'All APIs' : implode(', ', array_map('ucfirst', $this->types));
            FilamentNotification::make()
                ->title('API Sync Completed')
                ->body($syncTypesText . ' synced successfully in ' . $duration . ' seconds')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->sendToDatabase($recipients);
        } catch (Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            Log::error('Sequential sync job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration' => $duration,
                'attempt' => $this->attempts(),
            ]);

            // Notify superadmins via Filament database notifications
            $recipients = User::superadmin()->get();

            // Detailed error notification
            FilamentNotification::make()
                ->title('Sequential API Sync Failed')
                ->body('Error: ' . $e->getMessage() . ' | Duration: ' . $duration . 's | Attempt: ' . $this->attempts())
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->sendToDatabase($recipients);

            // Summary error notification
            FilamentNotification::make()
                ->title('API Sync Failed')
                ->body('Sync encountered an error. Check logs for details.')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->sendToDatabase($recipients);

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /** 
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Sequential sync job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'plant_codes' => $this->plantCodes,
            'running_time_range' => "{$this->runningTimeStartDate} to {$this->runningTimeEndDate}",
            'work_order_range' => "{$this->workOrderStartDate} to {$this->workOrderEndDate}",
        ]);

        // Notify superadmins via Filament database notifications
        $recipients = User::superadmin()->get();

        // Detailed permanent failure notification
        FilamentNotification::make()
            ->title('Sequential API Sync Failed Permanently')
            ->body('Job failed after all retry attempts. Error: ' . $exception->getMessage())
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->sendToDatabase($recipients);

        // Summary permanent failure notification
        FilamentNotification::make()
            ->title('API Sync Failed Permanently')
            ->body('Job failed after all retries. Manual intervention required.')
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->sendToDatabase($recipients);
    }
}
