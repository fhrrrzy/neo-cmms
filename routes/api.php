<?php

use App\Http\Controllers\Api\StatsApiController;
use App\Http\Controllers\Api\EquipmentSearchApiController;
use App\Http\Controllers\Api\SyncWebhookController;
use App\Http\Controllers\Api\RegionalApiController;
use App\Http\Controllers\Api\PabrikApiController;
use App\Http\Controllers\Api\SyncLogController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\EquipmentApiController;
use App\Http\Controllers\Api\WorkOrderApiController;
use App\Http\Controllers\Api\EquipmentWorkOrderApiController;
use App\Http\Controllers\EquipmentController;

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

// Regional API routes
Route::get('/regions', [RegionalApiController::class, 'index']);
Route::get('/regions/{identifier}', [RegionalApiController::class, 'show']);

// Pabrik (Plant) API routes
Route::get('/pabrik', [PabrikApiController::class, 'index']);
Route::get('/pabrik/{identifier}', [PabrikApiController::class, 'show']);

// Reference data for filters (legacy - kept for backward compatibility)
Route::get('/regions-filter', [MonitoringController::class, 'regions']);
Route::get('/plants', [MonitoringController::class, 'plants']);
Route::get('/stations', [MonitoringController::class, 'stations']);

// Equipment search API (with caching)
Route::get('/equipment/search', [EquipmentSearchApiController::class, 'search']);

// Equipment detail CSR API
Route::get('/equipment/{equipmentNumber}', [EquipmentApiController::class, 'show']);
// Equipment images by equipment UUID (masking by uuid)
Route::get('/equipment/{uuid}/images', [EquipmentController::class, 'images']);

// Work Orders by plant and date range
Route::get('/workorders', [WorkOrderApiController::class, 'index']);

// Equipment work orders (materials issued per equipment)
Route::get('/equipment-work-orders', [EquipmentWorkOrderApiController::class, 'index']);
Route::get('/equipment-work-orders/{orderNumber}', [EquipmentWorkOrderApiController::class, 'show']);

Route::get('/stats/overview', [StatsApiController::class, 'overview']);

// Webhook routes for API sync (protected by API key)
Route::prefix('webhook/sync')->middleware('webhook.key')->group(function () {
    Route::get('/equipment', [SyncWebhookController::class, 'syncEquipment']);
    Route::get('/running-time', [SyncWebhookController::class, 'syncRunningTime']);
    Route::get('/work-orders', [SyncWebhookController::class, 'syncWorkOrders']);
    Route::get('/equipment-work-orders', [SyncWebhookController::class, 'syncEquipmentWorkOrders']);
    Route::get('/equipment-materials', [SyncWebhookController::class, 'syncEquipmentMaterials']);
    Route::get('/daily-plant-data', [SyncWebhookController::class, 'syncDailyPlantData']);
    Route::get('/all', [SyncWebhookController::class, 'syncAll']);
});

// Sync Log API routes
Route::get('/sync-logs', [SyncLogController::class, 'index']);
Route::get('/sync-logs/stats', [SyncLogController::class, 'stats']);
Route::get('/sync-logs/by-type', [SyncLogController::class, 'byType']);
