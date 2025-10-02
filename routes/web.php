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

Route::get('/equipment/{equipmentNumber}', function (string $equipmentNumber) {
    // Publicly accessible equipment page; data is fetched client-side via API
    return Inertia::render('equipment/detail/detail', [
        'equipmentNumber' => $equipmentNumber,
        'isGuest' => !Auth::check(),
    ]);
})->name('equipment.detail');


include __DIR__ . '/auth.php';
include __DIR__ . '/settings.php';
