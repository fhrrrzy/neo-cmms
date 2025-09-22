<?php

namespace App\Services\Sync;

use App\Models\Equipment;
use App\Models\Plant;
use App\Models\EquipmentGroup;
use App\Models\ApiSyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class EquipmentSyncService
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
     * Synchronize equipment data from external API.
     *
     * @return ApiSyncLog
     */
    public function syncEquipment(): ApiSyncLog
    {
        $syncLog = $this->createSyncLog();
        
        try {
            $syncLog->update([
                'status' => ApiSyncLog::STATUS_RUNNING,
                'sync_started_at' => now(),
            ]);

            $equipmentData = $this->fetchEquipmentFromApi();
            $this->processEquipmentData($equipmentData, $syncLog);

            $syncLog->update([
                'status' => ApiSyncLog::STATUS_COMPLETED,
                'sync_completed_at' => now(),
            ]);

            Log::info('Equipment synchronization completed successfully', [
                'sync_log_id' => $syncLog->id,
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

            Log::error('Equipment synchronization failed', [
                'sync_log_id' => $syncLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $syncLog;
    }

    /**
     * Fetch equipment data from external API.
     *
     * @return array
     * @throws Exception
     */
    protected function fetchEquipmentFromApi(): array
    {
        $response = Http::withHeaders($this->apiHeaders)
            ->timeout(120)
            ->get($this->apiUrl . '/equipment');

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
     * Process equipment data and update database.
     *
     * @param array $equipmentData
     * @param ApiSyncLog $syncLog
     */
    protected function processEquipmentData(array $equipmentData, ApiSyncLog $syncLog): void
    {
        $processed = 0;
        $success = 0;
        $failed = 0;

        foreach ($equipmentData as $equipment) {
            $processed++;
            
            try {
                $this->validateEquipmentData($equipment);
                $this->upsertEquipment($equipment);
                $success++;
            } catch (Exception $e) {
                $failed++;
                Log::warning('Failed to process equipment record', [
                    'equipment_number' => $equipment['EQUNR'] ?? 'unknown',
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
     * Validate equipment data from API.
     *
     * @param array $equipment
     * @throws Exception
     */
    protected function validateEquipmentData(array $equipment): void
    {
        $requiredFields = ['EQUNR', 'SWERK', 'BUKRS'];
        
        foreach ($requiredFields as $field) {
            if (!isset($equipment[$field]) || empty($equipment[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validate equipment number length
        if (strlen($equipment['EQUNR']) > 50) {
            throw new Exception("Equipment number too long: " . $equipment['EQUNR']);
        }
    }

    /**
     * Upsert equipment record in database.
     *
     * @param array $equipment
     * @throws Exception
     */
    protected function upsertEquipment(array $equipment): void
    {
        // Find or create plant
        $plant = Plant::firstOrCreate(
            ['plant_code' => $equipment['SWERK']],
            [
                'name' => $equipment['SWERK'],
                'description' => 'Auto-created from API sync',
                'is_active' => true,
            ]
        );

        // Find or create equipment group (using a default for now)
        $equipmentGroup = EquipmentGroup::firstOrCreate(
            ['name' => 'Default Equipment Group'],
            [
                'description' => 'Default group for equipment from API',
                'is_active' => true,
            ]
        );

        // Upsert equipment
        Equipment::updateOrCreate(
            ['equipment_number' => $equipment['EQUNR']],
            [
                'plant_id' => $plant->id,
                'equipment_group_id' => $equipmentGroup->id,
                'company_code' => $equipment['BUKRS'] ?? null,
                'equipment_description' => $equipment['EQKTU'] ?? null,
                'object_number' => $equipment['OBJNR'] ?? null,
                'point' => $equipment['POINT'] ?? null,
                'api_created_at' => isset($equipment['CREATED_AT']) 
                    ? \Carbon\Carbon::parse($equipment['CREATED_AT'])
                    : null,
                'is_active' => true,
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
            'sync_type' => ApiSyncLog::SYNC_TYPE_EQUIPMENT,
            'status' => ApiSyncLog::STATUS_PENDING,
            'records_processed' => 0,
            'records_success' => 0,
            'records_failed' => 0,
        ]);
    }
}