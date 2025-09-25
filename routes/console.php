<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ConcurrentSyncJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks (Queue-Based)
|--------------------------------------------------------------------------
|
| Here you may define the scheduled tasks for the CMMS synchronization
| system. These tasks use Laravel's queue system instead of cron.
|
*/

// Schedule concurrent synchronization daily at midnight using queue
Schedule::job(new ConcurrentSyncJob())
    ->daily()
    ->at('00:00')
    ->name('daily-concurrent-sync-job')
    ->description('Daily concurrent synchronization of all APIs (equipment, running time, work orders) via queue')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('Scheduled concurrent sync job failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled concurrent sync job completed successfully');
    });

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
