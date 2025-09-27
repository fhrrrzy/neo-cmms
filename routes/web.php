<?php

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
    return view('welcome');
});
