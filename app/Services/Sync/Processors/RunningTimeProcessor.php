<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\RunningTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class RunningTimeProcessor
{
    private const BATCH_SIZE = 2000;

    /**
     * Process a single running time item (legacy method for backward compatibility)
     */
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        $this->processBatch([$item], $allowedPlantCodes);
    }

    /**
     * Process running time items in batches for optimal performance
     */
    public function processBatch(array $items, array $allowedPlantCodes = []): void
    {
        if (empty($items)) {
            return;
        }

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($items, self::BATCH_SIZE);
        
        foreach ($chunks as $chunk) {
            $this->processChunk($chunk, $allowedPlantCodes);
        }
    }

    /**
     * Process a chunk of running time items
     */
    private function processChunk(array $chunk, array $allowedPlantCodes = []): void
    {
        DB::transaction(function () use ($chunk, $allowedPlantCodes) {
            // Pre-load all plants in bulk
            $plantCodes = collect($chunk)
                ->map(function ($item) {
                    return Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $plants = Plant::whereIn('plant_code', $plantCodes)->get()->keyBy('plant_code');

            // Prepare running time data for bulk upsert
            $runningTimeData = [];
            
            foreach ($chunk as $item) {
                $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
                
                // Skip if plant code not in allowed list
                if (!empty($allowedPlantCodes) && ($plantCode === null || !in_array($plantCode, $allowedPlantCodes, true))) {
                    continue;
                }

                $plant = $plants[$plantCode] ?? null;
                if (!$plant) {
                    Log::warning('RunningTimeProcessor: Plant not found', [
                        'plant_code' => $plantCode,
                        'available_plants' => $plants->pluck('plant_code')->toArray()
                    ]);
                    continue;
                }

                $equipmentNumber = Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR');
                $date = Arr::get($item, 'date') ?? Arr::get($item, 'DATE');
                
                if (!$equipmentNumber || !$date) {
                    Log::error('RunningTimeProcessor: Missing required fields', [
                        'equipment_number' => $equipmentNumber,
                        'date' => $date,
                        'item_data' => $item
                    ]);
                    continue;
                }

                $runningTimeData[] = $this->prepareRunningTimeData($item, $plant);
            }
            
            // Bulk upsert running time data
            $this->bulkUpsertRunningTime($runningTimeData);
        });
    }

    /**
     * Prepare running time data for bulk upsert
     */
    private function prepareRunningTimeData(array $item, Plant $plant): array
    {
        $equipmentNumber = Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR');
        $date = Arr::get($item, 'date') ?? Arr::get($item, 'DATE');

        $apiCreatedAt = null;
        $ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT');
        if ($ts) {
            try {
                $apiCreatedAt = Carbon::parse($ts);
            } catch (\Exception $e) {
                // Skip invalid dates
            }
        }

        return [
            'equipment_number' => $equipmentNumber,
            'date' => $date,
            'plant_id' => $plant->id,
            'mandt' => Arr::get($item, 'MANDT'),
            'point' => Arr::get($item, 'POINT'),
            'date_time' => Arr::get($item, 'date_time') ?? Arr::get($item, 'DATE_TIME'),
            'running_hours' => Arr::get($item, 'running_hours') ?? Arr::get($item, 'RECDV'),
            'counter_reading' => Arr::get($item, 'counter_reading') ?? Arr::get($item, 'CNTRR'),
            'maintenance_text' => Arr::get($item, 'maintenance_text') ?? Arr::get($item, 'MDTXT'),
            'company_code' => Arr::get($item, 'company_code') ?? Arr::get($item, 'BUKRS'),
            'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'EQKTU'),
            'object_number' => Arr::get($item, 'object_number') ?? Arr::get($item, 'OBJNR'),
            'api_created_at' => $apiCreatedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Bulk upsert running time using Eloquent for better performance and safety
     */
    private function bulkUpsertRunningTime(array $runningTimeData): void
    {
        if (empty($runningTimeData)) {
            return;
        }

        // Use Eloquent's upsert for better performance and safety
        RunningTime::upsert(
            $runningTimeData,
            ['equipment_number', 'date'], // unique keys
            [
                'plant_id', 'mandt', 'point', 'date_time', 'running_hours',
                'counter_reading', 'maintenance_text', 'company_code',
                'equipment_description', 'object_number', 'api_created_at', 'updated_at'
            ]
        );
    }
}
