<?php

use Inertia\Inertia;
use App\Http\Controllers\EquipmentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Health check endpoint for Docker
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'services' => [
            'database' => 'connected',
            'redis' => 'connected',
            'storage' => 'writable'
        ]
    ]);
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard')->middleware('auth');

Route::get('/monitoring', function () {
    return Inertia::render('monitoring/Monitoring');
})->name('monitoring')->middleware('auth');

Route::get('/jam-jalan-summary', function () {
    return Inertia::render('jam-jalan-summary/Index');
})->name('jam-jalan-summary')->middleware('auth');

Route::get('/equipment/{uuid}', function (string $uuid) {
    // Publicly accessible equipment page; data is fetched client-side via API
    return Inertia::render('equipment/detail/detail', [
        'uuid' => $uuid,
        'isGuest' => !Auth::check(),
    ]);
})->name('equipment.detail');

// Regional routes
Route::get('/regions', function () {
    return Inertia::render('regions/RegionalList');
})->name('regions.index')->middleware('auth');

Route::get('/regions/{uuid}', function (string $uuid) {
    return Inertia::render('regions/RegionalDetail', [
        'uuid' => $uuid,
    ]);
})->name('regions.show')->middleware('auth')->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

// Pabrik (Plant) routes
Route::get('/pabrik', function () {
    return Inertia::render('pabrik/PabrikList');
})->name('pabrik.index')->middleware('auth');

Route::get('/pabrik/{uuid}', function (string $uuid) {
    return Inertia::render('pabrik/PabrikDetail', [
        'uuid' => $uuid,
    ]);
})->name('pabrik.show')->middleware('auth')->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

// Sync Log route
Route::get('/sync-log', [\App\Http\Controllers\SyncLogController::class, 'index'])
    ->name('sync-log.index')
    ->middleware('auth');


include __DIR__ . '/auth.php';
include __DIR__ . '/settings.php';
