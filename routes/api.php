<?php

use App\Http\Controllers\Api\StatsApiController;
use App\Http\Controllers\Api\EquipmentSearchApiController;
use App\Http\Controllers\Api\SyncWebhookController;

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
    Route::get('/jam-jalan-summary', [MonitoringController::class, 'jamJalanSummary']);
    Route::get('/jam-jalan-detail', [MonitoringController::class, 'jamJalanDetail']);
});

// Equipment API routes - removed, now using Inertia data passing

// Reference data for filters
Route::get('/regions', [MonitoringController::class, 'regions']);
Route::get('/plants', [MonitoringController::class, 'plants']);
Route::get('/stations', [MonitoringController::class, 'stations']);

// Equipment search API (with caching)
Route::get('/equipment/search', [EquipmentSearchApiController::class, 'search']);

// Equipment detail CSR API
Route::get('/equipment/{equipmentNumber}', [EquipmentApiController::class, 'show']);

// Work Orders by plant and date range
Route::get('/workorders', [WorkOrderApiController::class, 'index']);

// Equipment work orders (materials issued per equipment)
Route::get('/equipment-work-orders', [EquipmentWorkOrderApiController::class, 'index']);
Route::get('/equipment-work-orders/{orderNumber}', [EquipmentWorkOrderApiController::class, 'show']);

Route::get('/stats/overview', [StatsApiController::class, 'overview']);

// Webhook routes for API sync
Route::prefix('webhook/sync')->group(function () {
    Route::get('/equipment', [SyncWebhookController::class, 'syncEquipment']);
    Route::get('/running-time', [SyncWebhookController::class, 'syncRunningTime']);
    Route::get('/work-orders', [SyncWebhookController::class, 'syncWorkOrders']);
    Route::get('/equipment-work-orders', [SyncWebhookController::class, 'syncEquipmentWorkOrders']);
    Route::get('/equipment-materials', [SyncWebhookController::class, 'syncEquipmentMaterials']);
    Route::get('/daily-plant-data', [SyncWebhookController::class, 'syncDailyPlantData']);
    Route::get('/all', [SyncWebhookController::class, 'syncAll']);
});
