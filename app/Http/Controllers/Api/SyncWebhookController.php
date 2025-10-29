<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sync\ConcurrentApiSyncService;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SyncWebhookController extends Controller
{
    protected ConcurrentApiSyncService $syncService;

    public function __construct(ConcurrentApiSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Sync equipment data for all active plants
     * GET /api/webhook/sync/equipment
     */
    public function syncEquipment(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);

            Log::info('Equipment sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                types: ['equipment']
            );

            return response()->json([
                'success' => true,
                'message' => 'Equipment sync completed',
                'data' => $result['equipment'] ?? [],
            ], 200);
        } catch (Exception $e) {
            Log::error('Equipment sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Equipment sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync running time data for all active plants
     * GET /api/webhook/sync/running-time
     * Parameters: start_date (optional), end_date (optional)
     */
    public function syncRunningTime(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);
            $startDate = $request->input('start_date', Carbon::now()->subDays(3)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            // Validate dates
            $this->validateDateRange($startDate, $endDate);

            Log::info('Running time sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                runningTimeStartDate: $startDate,
                runningTimeEndDate: $endDate,
                types: ['running_time']
            );

            return response()->json([
                'success' => true,
                'message' => 'Running time sync completed',
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data' => $result['running_time'] ?? [],
            ], 200);
        } catch (Exception $e) {
            Log::error('Running time sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Running time sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync work orders for all active plants
     * GET /api/webhook/sync/work-orders
     * Parameters: start_date (optional), end_date (optional)
     */
    public function syncWorkOrders(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);
            $startDate = $request->input('start_date', Carbon::now()->subDays(3)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            // Validate dates
            $this->validateDateRange($startDate, $endDate);

            Log::info('Work orders sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                workOrderStartDate: $startDate,
                workOrderEndDate: $endDate,
                types: ['work_orders']
            );

            return response()->json([
                'success' => true,
                'message' => 'Work orders sync completed',
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data' => $result['work_orders'] ?? [],
            ], 200);
        } catch (Exception $e) {
            Log::error('Work orders sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Work orders sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync equipment work orders for all active plants
     * GET /api/webhook/sync/equipment-work-orders
     * Parameters: start_date (optional), end_date (optional)
     */
    public function syncEquipmentWorkOrders(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);
            $startDate = $request->input('start_date', Carbon::now()->subDays(3)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            // Validate dates
            $this->validateDateRange($startDate, $endDate);

            Log::info('Equipment work orders sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                workOrderStartDate: $startDate,
                workOrderEndDate: $endDate,
                types: ['equipment_work_orders']
            );

            return response()->json([
                'success' => true,
                'message' => 'Equipment work orders sync completed',
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data' => $result['equipment_work_orders'] ?? [],
            ], 200);
        } catch (Exception $e) {
            Log::error('Equipment work orders sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Equipment work orders sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync equipment materials for all active plants
     * GET /api/webhook/sync/equipment-materials
     * Parameters: start_date (optional), end_date (optional)
     */
    public function syncEquipmentMaterials(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);
            $startDate = $request->input('start_date', Carbon::now()->subDays(3)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            // Validate dates
            $this->validateDateRange($startDate, $endDate);

            Log::info('Equipment materials sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                workOrderStartDate: $startDate,
                workOrderEndDate: $endDate,
                types: ['equipment_materials']
            );

            return response()->json([
                'success' => true,
                'message' => 'Equipment materials sync completed',
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data' => $result['equipment_materials'] ?? [],
            ], 200);
        } catch (Exception $e) {
            Log::error('Equipment materials sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Equipment materials sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync daily plant data for all active plants
     * GET /api/webhook/sync/daily-plant-data
     * Parameters: start_date (optional), end_date (optional)
     */
    public function syncDailyPlantData(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);
            $startDate = $request->input('start_date', Carbon::now()->subDays(3)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            // Validate dates
            $this->validateDateRange($startDate, $endDate);

            Log::info('Daily plant data sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                runningTimeStartDate: $startDate,
                runningTimeEndDate: $endDate,
                types: ['daily_plant_data']
            );

            return response()->json([
                'success' => true,
                'message' => 'Daily plant data sync completed',
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data' => $result['daily_plant_data'] ?? [],
            ], 200);
        } catch (Exception $e) {
            Log::error('Daily plant data sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Daily plant data sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all data types sequentially
     * GET /api/webhook/sync/all
     * Parameters: start_date (optional), end_date (optional)
     */
    public function syncAll(Request $request)
    {
        try {
            $plantCodes = $this->getPlantCodes($request);
            $startDate = $request->input('start_date', Carbon::now()->subDays(3)->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->toDateString());

            // Validate dates
            $this->validateDateRange($startDate, $endDate);

            Log::info('Full sync webhook triggered', [
                'plant_codes' => $plantCodes,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'ip' => $request->ip(),
            ]);

            $result = $this->syncService->syncAllSequentially(
                plantCodes: $plantCodes,
                runningTimeStartDate: $startDate,
                runningTimeEndDate: $endDate,
                workOrderStartDate: $startDate,
                workOrderEndDate: $endDate
            );

            return response()->json([
                'success' => true,
                'message' => 'Full sync completed',
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            Log::error('Full sync webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Full sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get plant codes from request or use all active plants
     */
    protected function getPlantCodes(Request $request): array
    {
        // If plant_codes are provided in the request, use them
        if ($request->has('plant_codes')) {
            $plantCodes = $request->input('plant_codes');
            return is_array($plantCodes) ? $plantCodes : explode(',', $plantCodes);
        }

        // Otherwise, use all active plants
        return Plant::where('is_active', true)->pluck('plant_code')->toArray();
    }

    /**
     * Validate date range
     */
    protected function validateDateRange(string $startDate, string $endDate): void
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->isAfter($end)) {
                throw new Exception('Start date must be before or equal to end date');
            }
        } catch (Exception $e) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD format.');
        }
    }
}
