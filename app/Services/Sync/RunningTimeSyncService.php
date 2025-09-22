<?php

namespace App\Services\Sync;

use App\Models\Equipment;
use App\Models\EquipmentRunningTime;
use App\Models\Plant;
use App\Models\ApiSyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class RunningTimeSyncService
{
    protected string $apiUrl;
    protected array $apiHeaders;

    public function __construct()
    {
        $this->apiUrl = config('services.equipment_api.url');
        $this->apiHeaders = [
            'Authorization' => 'Bearer ' . config('services.equipment_api.token'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Synchronize running time data from external API.
     *
     * @param string|null $date Date to sync (Y-m-d format). Defaults to yesterday.
     * @return ApiSyncLog
     */
    public function syncRunningTime(?string $date = null): ApiSyncLog
    {
        $syncDate = $date ? Carbon::parse($date) : Carbon::yesterday();
        $syncLog = $this->createSyncLog();
        
        try {
            $syncLog->update([
                'status' => ApiSyncLog::STATUS_RUNNING,
                'sync_started_at' => now(),
            ]);

            $runningTimeData = $this->fetchRunningTimeFromApi($syncDate);
            $this->processRunningTimeData($runningTimeData, $syncLog);

            $syncLog->update([
                'status' => ApiSyncLog::STATUS_COMPLETED,
                'sync_completed_at' => now(),
            ]);

            Log::info('Running time synchronization completed successfully', [
                'sync_log_id' => $syncLog->id,
                'sync_date' => $syncDate->toDateString(),
                'records_processed' => $syncLog->records_processed,
                'records_success' => $syncLog->records_success,
                'records_failed' => $syncLog->records_failed,
            ]);

        } catch (Exception $e) {
            $syncLog->update([
                'status' => ApiSyncLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'sync_completed_at' => now(),
            ]);

            Log::error('Running time synchronization failed', [
                'sync_log_id' => $syncLog->id,
                'sync_date' => $syncDate->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $syncLog;
    }

    /**
     * Fetch running time data from external API.
     *
     * @param Carbon $date
     * @return array
     * @throws Exception
     */
    protected function fetchRunningTimeFromApi(Carbon $date): array
    {
        $response = Http::withHeaders($this->apiHeaders)
            ->timeout(120)
            ->get($this->apiUrl . '/running-time', [
                'date' => $date->toDateString(),
            ]);

        if (!$response->successful()) {
            throw new Exception("API request failed: " . $response->body());
        }

        $data = $response->json();
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new Exception("Invalid API response format");
        }

        return $data['data'];
    }

    /**
     * Process running time data and update database.
     *
     * @param array $runningTimeData
     * @param ApiSyncLog $syncLog
     */
    protected function processRunningTimeData(array $runningTimeData, ApiSyncLog $syncLog): void
    {
        $processed = 0;
        $success = 0;
        $failed = 0;

        foreach ($runningTimeData as $runningTime) {
            $processed++;
            
            try {
                $this->validateRunningTimeData($runningTime);
                $this->upsertRunningTime($runningTime);
                $success++;
            } catch (Exception $e) {
                $failed++;
                Log::warning('Failed to process running time record', [
                    'equipment_number' => $runningTime['EQUNR'] ?? 'unknown',
                    'date' => $runningTime['DATE'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $syncLog->update([
            'records_processed' => $processed,
            'records_success' => $success,
            'records_failed' => $failed,
        ]);
    }

    /**
     * Validate running time data from API.
     *
     * @param array $runningTime
     * @throws Exception
     */
    protected function validateRunningTimeData(array $runningTime): void
    {
        $requiredFields = ['EQUNR', 'SWERK', 'DATE', 'RECDV', 'CNTRR'];
        
        foreach ($requiredFields as $field) {
            if (!isset($runningTime[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validate numeric fields
        if (!is_numeric($runningTime['RECDV'])) {
            throw new Exception("Invalid running hours: " . $runningTime['RECDV']);
        }

        if (!is_numeric($runningTime['CNTRR'])) {
            throw new Exception("Invalid cumulative hours: " . $runningTime['CNTRR']);
        }

        // Validate date format
        try {
            Carbon::parse($runningTime['DATE']);
        } catch (Exception $e) {
            throw new Exception("Invalid date format: " . $runningTime['DATE']);
        }

        // Validate that cumulative hours is not less than running hours
        if ((float) $runningTime['CNTRR'] < (float) $runningTime['RECDV']) {
            throw new Exception("Cumulative hours cannot be less than running hours");
        }
    }

    /**
     * Upsert running time record in database.
     *
     * @param array $runningTime
     * @throws Exception
     */
    protected function upsertRunningTime(array $runningTime): void
    {
        // Find equipment by equipment number
        $equipment = Equipment::where('equipment_number', $runningTime['EQUNR'])->first();
        
        if (!$equipment) {
            throw new Exception("Equipment not found: " . $runningTime['EQUNR']);
        }

        // Find plant by plant code
        $plant = Plant::where('plant_code', $runningTime['SWERK'])->first();
        
        if (!$plant) {
            throw new Exception("Plant not found: " . $runningTime['SWERK']);
        }

        $date = Carbon::parse($runningTime['DATE'])->toDateString();

        // Check for existing record to validate cumulative hours progression
        $existingRecord = EquipmentRunningTime::where('equipment_id', $equipment->id)
            ->where('date', $date)
            ->first();

        $cumulativeHours = (float) $runningTime['CNTRR'];
        
        // Validate cumulative hours progression if this is an update
        if ($existingRecord && $cumulativeHours < $existingRecord->cumulative_hours) {
            // Allow update only if the new cumulative hours is reasonable
            $previousDay = EquipmentRunningTime::where('equipment_id', $equipment->id)
                ->where('date', '<', $date)
                ->orderBy('date', 'desc')
                ->first();
                
            if ($previousDay && $cumulativeHours < $previousDay->cumulative_hours) {
                throw new Exception("Cumulative hours regression detected for equipment: " . $runningTime['EQUNR']);
            }
        }

        // Upsert running time record
        EquipmentRunningTime::updateOrCreate(
            [
                'equipment_id' => $equipment->id,
                'date' => $date,
            ],
            [
                'plant_id' => $plant->id,
                'point' => $runningTime['POINT'] ?? null,
                'date_time' => isset($runningTime['DATE_TIME']) 
                    ? Carbon::parse($runningTime['DATE_TIME'])
                    : null,
                'description' => $runningTime['MDTXT'] ?? null,
                'running_hours' => (float) $runningTime['RECDV'],
                'cumulative_hours' => $cumulativeHours,
                'company_code' => $runningTime['BUKRS'] ?? null,
                'equipment_description' => $runningTime['EQKTU'] ?? null,
                'object_number' => $runningTime['OBJNR'] ?? null,
                'api_created_at' => isset($runningTime['CREATED_AT']) 
                    ? Carbon::parse($runningTime['CREATED_AT'])
                    : null,
            ]
        );
    }

    /**
     * Create initial sync log record.
     *
     * @return ApiSyncLog
     */
    protected function createSyncLog(): ApiSyncLog
    {
        return ApiSyncLog::create([
            'sync_type' => ApiSyncLog::SYNC_TYPE_RUNNING_TIME,
            'status' => ApiSyncLog::STATUS_PENDING,
            'records_processed' => 0,
            'records_success' => 0,
            'records_failed' => 0,
        ]);
    }

    /**
     * Sync running time for a specific date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array Array of ApiSyncLog instances
     */
    public function syncDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $syncLogs = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            try {
                $syncLog = $this->syncRunningTime($currentDate->toDateString());
                $syncLogs[] = $syncLog;
            } catch (Exception $e) {
                Log::error('Failed to sync running time for date', [
                    'date' => $currentDate->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }

            $currentDate->addDay();
        }

        return $syncLogs;
    }
}