<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncEquipmentJob;
use App\Jobs\SyncRunningTimeJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define the scheduled tasks for the CMMS synchronization
| system. These tasks will be executed automatically by the Laravel
| scheduler when properly configured with cron.
|
*/

// Schedule equipment synchronization daily at midnight
Schedule::job(new SyncEquipmentJob())
    ->daily()
    ->at('00:00')
    ->name('daily-equipment-sync')
    ->description('Daily synchronization of equipment data from external API')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('Scheduled equipment sync failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled equipment sync completed successfully');
    });

// Schedule running time synchronization daily at 1:00 AM
Schedule::job(new SyncRunningTimeJob())
    ->daily()
    ->at('01:00')
    ->name('daily-running-time-sync')
    ->description('Daily synchronization of equipment running time data from external API')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::critical('Scheduled running time sync failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled running time sync completed successfully');
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

// Schedule queue monitoring to ensure queue workers are healthy
Schedule::call(function () {
    $queueSize = \Illuminate\Support\Facades\Queue::size();

    if ($queueSize > 1000) {
        \Illuminate\Support\Facades\Log::warning('Queue size is getting large', ['queue_size' => $queueSize]);
    }

    // Check for stuck jobs (running for more than 2 hours)
    $stuckJobs = \App\Models\ApiSyncLog::where('status', 'running')
        ->where('sync_started_at', '<', now()->subHours(2))
        ->count();

    if ($stuckJobs > 0) {
        \Illuminate\Support\Facades\Log::warning('Stuck sync jobs detected', ['stuck_jobs_count' => $stuckJobs]);
    }
})
    ->everyFifteenMinutes()
    ->name('queue-health-check')
    ->description('Monitor queue health and detect stuck jobs');

// Direct command-based scheduling (ensures sync even if jobs are not configured)
Schedule::command('sync:equipments')
    ->dailyAt('00:15')
    ->name('daily-equipments-sync-command')
    ->description('Daily sync of equipments via command');

Schedule::command('sync:running-time')
    ->dailyAt('00:30')
    ->name('daily-running-time-sync-command')
    ->description('Daily sync of running time via command');
