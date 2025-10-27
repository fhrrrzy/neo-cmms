<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\EquipmentApiController;
use App\Http\Controllers\Api\WorkOrderApiController;
use App\Http\Controllers\Api\EquipmentWorkOrderApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Monitoring API routes
Route::prefix('monitoring')->group(function () {
    Route::get('/equipment', [MonitoringController::class, 'equipment']);
    Route::get('/biaya', [MonitoringController::class, 'biaya']);
});

// Equipment API routes - removed, now using Inertia data passing

// Reference data for filters
Route::get('/regions', [MonitoringController::class, 'regions']);
Route::get('/plants', [MonitoringController::class, 'plants']);
Route::get('/stations', [MonitoringController::class, 'stations']);

// Equipment detail CSR API
Route::get('/equipment/{equipmentNumber}', [EquipmentApiController::class, 'show']);

// Work Orders by plant and date range
Route::get('/workorders', [WorkOrderApiController::class, 'index']);

// Equipment work orders (materials issued per equipment)
Route::get('/equipment-work-orders', [EquipmentWorkOrderApiController::class, 'index']);
Route::get('/equipment-work-orders/{orderNumber}', [EquipmentWorkOrderApiController::class, 'show']);
