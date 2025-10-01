<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\EquipmentApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Monitoring API routes
Route::prefix('monitoring')->group(function () {
    Route::get('/equipment', [MonitoringController::class, 'equipment']);
});

// Equipment API routes - removed, now using Inertia data passing

// Reference data for filters
Route::get('/regions', [MonitoringController::class, 'regions']);
Route::get('/plants', [MonitoringController::class, 'plants']);
Route::get('/stations', [MonitoringController::class, 'stations']);

// Equipment detail CSR API
Route::get('/equipment/{equipmentNumber}', [EquipmentApiController::class, 'show']);
