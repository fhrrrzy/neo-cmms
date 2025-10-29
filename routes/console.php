<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ConcurrentSyncJob;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Task Scheduling (Pure Laravel - No Cron Required)
|--------------------------------------------------------------------------
|
| Laravel's schedule:work command eliminates the need for cron entirely!
| Just run: php artisan schedule:work
|
*/

// Schedule sequential synchronization every 6 hours using queue
Schedule::job(new ConcurrentSyncJob())
    ->cron('0 */6 * * *')
    ->name('sequential-sync-job-6h')
    ->description('Sequential synchronization of all APIs (equipment → running_time → work_orders → equipment_work_order_materials) every 6 hours with 3-day range')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('Scheduled sequential sync job failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled sequential sync job completed successfully');
    });

// Additionally, dispatch per-plant jobs asynchronously every 6 hours to spread load
Schedule::call(function () {
    $plants = \App\Models\Plant::where('is_active', true)->pluck('plant_code')->toArray();
    foreach ($plants as $plantCode) {
        dispatch(new ConcurrentSyncJob([$plantCode]));
    }
    \Illuminate\Support\Facades\Log::info('Dispatched per-plant sequential sync jobs');
})
    ->cron('5 */6 * * *')
    ->name('per-plant-sequential-sync-6h')
    ->description('Dispatch sequential sync jobs per plant every 6 hours for async by plant');

// Schedule cleanup of old sync logs (keep last 30 days)
Schedule::call(function () {
    $deletedCount = \App\Models\ApiSyncLog::where('created_at', '<', now()->subDays(30))->delete();
    \Illuminate\Support\Facades\Log::info('Old sync logs cleaned up', ['deleted_count' => $deletedCount]);
})
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->name('cleanup-old-sync-logs')
    ->description('Clean up sync logs older than 30 days');

// Schedule sync health monitoring
Schedule::call(function () {
    // Check for stuck syncs (running for more than 2 hours)
    $stuckSyncs = \App\Models\ApiSyncLog::where('status', 'running')
        ->where('sync_started_at', '<', now()->subHours(2))
        ->count();

    if ($stuckSyncs > 0) {
        \Illuminate\Support\Facades\Log::warning('Stuck sync operations detected', ['stuck_syncs_count' => $stuckSyncs]);
    }

    // Log sync statistics
    $todaySyncs = \App\Models\ApiSyncLog::whereDate('created_at', today())->count();
    $successfulSyncs = \App\Models\ApiSyncLog::whereDate('created_at', today())
        ->where('status', 'completed')
        ->count();

    \Illuminate\Support\Facades\Log::info('Daily sync statistics', [
        'total_syncs' => $todaySyncs,
        'successful_syncs' => $successfulSyncs,
        'success_rate' => $todaySyncs > 0 ? round(($successfulSyncs / $todaySyncs) * 100, 2) : 0,
    ]);
})
    ->daily()
    ->at('01:00')
    ->name('sync-health-monitor')
    ->description('Monitor sync health and log statistics');
