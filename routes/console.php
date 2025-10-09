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
    ->description('Sequential synchronization of all APIs (equipment → running_time → work_orders → equipment_work_orders → equipment_material) every 6 hours with 3-day range')
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

// --- IMS API probe commands (real API calls) ---
Artisan::command('ims:test:equipment {plant* : One or more plant codes}', function () {
    $baseUrl = rtrim(config('ims.base_url'), '/');
    $token = config('ims.token');
    $plants = (array) $this->argument('plant');
    $url = $baseUrl . '/equipments';
    $this->info("GET {$url} (JSON body)");
    $res = Http::withHeaders(['Authorization' => $token])->asJson()->send('GET', $url, [
        'json' => ['plant' => array_values($plants)],
    ]);
    $this->line('HTTP ' . $res->status());
    $data = $res->json();
    $items = is_array($data) ? ($data['data'] ?? $data) : [];
    $this->line('items: ' . (is_array($items) ? count($items) : 0));
})->purpose('Probe IMS Equipments API');

Artisan::command('ims:test:material {plant*} {--start=} {--end=}', function () {
    $baseUrl = rtrim(config('ims.base_url'), '/');
    $token = config('ims.token');
    $plants = (array) $this->argument('plant');
    $start = $this->option('start') ?: now()->toDateString();
    $end = $this->option('end') ?: now()->toDateString();
    $url = $baseUrl . '/equipments/material?start_date=' . urlencode($start) . '&end_date=' . urlencode($end);
    $this->info("GET {$url} (JSON body)");
    $res = Http::withHeaders(['Authorization' => $token])->asJson()->send('GET', $url, [
        'json' => ['plant' => array_values($plants)],
    ]);
    $this->line('HTTP ' . $res->status());
    $data = $res->json();
    $items = is_array($data) ? ($data['data'] ?? $data) : [];
    $this->line('items: ' . (is_array($items) ? count($items) : 0));
})->purpose('Probe IMS Equipment Material API');

Artisan::command('ims:test:eq-wo {plant*} {--start=} {--end=} {--material=000000}', function () {
    $baseUrl = rtrim(config('ims.base_url'), '/');
    $token = config('ims.token');
    $plants = (array) $this->argument('plant');
    $start = $this->option('start') ?: now()->toDateString();
    $end = $this->option('end') ?: now()->toDateString();
    $material = $this->option('material') ?: '000000';
    $url = $baseUrl . '/equipments/work-order?start_date=' . urlencode($start) . '&end_date=' . urlencode($end);
    $this->info("GET {$url} (JSON body)");
    $res = Http::withHeaders(['Authorization' => $token])->asJson()->send('GET', $url, [
        'json' => ['plant' => array_values($plants), 'material_number' => $material],
    ]);
    $this->line('HTTP ' . $res->status());
    $data = $res->json();
    $items = is_array($data) ? ($data['data'] ?? $data) : [];
    $this->line('items: ' . (is_array($items) ? count($items) : 0));
})->purpose('Probe IMS Equipment Work Orders API');

Artisan::command('ims:test:work-orders {plant*} {--start=} {--end=}', function () {
    $baseUrl = rtrim(config('ims.base_url'), '/');
    $token = config('ims.token');
    $plants = (array) $this->argument('plant');
    $start = $this->option('start') ?: now()->toDateString();
    $end = $this->option('end') ?: now()->toDateString();
    $url = $baseUrl . '/work-order?start_date=' . urlencode($start) . '&end_date=' . urlencode($end);
    $this->info("GET {$url} (JSON body)");
    $res = Http::withHeaders(['Authorization' => $token])->asJson()->send('GET', $url, [
        'json' => ['plant' => array_values($plants)],
    ]);
    $this->line('HTTP ' . $res->status());
    $data = $res->json();
    $items = is_array($data) ? ($data['data'] ?? $data) : [];
    $this->line('items: ' . (is_array($items) ? count($items) : 0));
})->purpose('Probe IMS Work Orders API');

Artisan::command('ims:test:running-time {plant*} {--start=} {--end=}', function () {
    $baseUrl = rtrim(config('ims.base_url'), '/');
    $token = config('ims.token');
    $plants = (array) $this->argument('plant');
    $start = $this->option('start') ?: now()->toDateString();
    $end = $this->option('end') ?: now()->toDateString();
    foreach ($plants as $plant) {
        $url = $baseUrl . '/equipments/jam-jalan?start_date=' . urlencode($start) . '&end_date=' . urlencode($end);
        $this->info("GET {$url} (JSON body) PLANT={$plant}");
        $res = Http::withHeaders(['Authorization' => $token])->asJson()->send('GET', $url, [
            'json' => ['plant' => [$plant]],
        ]);
        $this->line('HTTP ' . $res->status());
        $data = $res->json();
        $items = is_array($data) ? ($data['data'] ?? $data) : [];
        $this->line("items: " . (is_array($items) ? count($items) : 0));
    }
})->purpose('Probe IMS Running Time API');
