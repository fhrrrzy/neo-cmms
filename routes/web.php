<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

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


include __DIR__ . '/auth.php';
include __DIR__ . '/settings.php';
