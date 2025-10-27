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
            'uuid' => \Illuminate\Support\Str::uuid(),
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
                'plant_id',
                'mandt',
                'point',
                'date_time',
                'running_hours',
                'counter_reading',
                'maintenance_text',
                'company_code',
                'equipment_description',
                'object_number',
                'api_created_at',
                'updated_at'
            ]
        );

        // Fill missing dates after upsert
        $this->fillMissingDates($runningTimeData);
    }

    /**
     * Fill missing dates by duplicating the last available record
     * When API returns nothing for a date, we duplicate the previous date's data
     * but set running_hours to 0 to indicate no activity that day
     * This also handles backdated data by re-scanning all records after sync
     */
    private function fillMissingDates(array $insertedData): void
    {
        if (empty($insertedData)) {
            return;
        }

        // Group by equipment_number
        $groupedByEquipment = [];
        foreach ($insertedData as $row) {
            $groupedByEquipment[$row['equipment_number']][] = $row;
        }

        $uniqueEquipmentNumbers = array_unique(array_column($insertedData, 'equipment_number'));

        // Re-scan all records for each equipment to ensure gaps are filled
        // This handles backdated data - when new old data is added, it fills gaps between old and new ranges
        foreach ($uniqueEquipmentNumbers as $equipmentNumber) {
            $this->fillGapsForEquipment($equipmentNumber);
        }
    }

    /**
     * Fill gaps for a specific equipment by scanning all existing records
     * This handles both forward and backdated data
     */
    private function fillGapsForEquipment(string $equipmentNumber): void
    {
        // Get ALL existing records for this equipment, ordered by date
        $existingRecords = RunningTime::where('equipment_number', $equipmentNumber)
            ->orderBy('date')
            ->get();

        // If no records, this is first sync - don't fill gaps
        if ($existingRecords->isEmpty()) {
            return;
        }

        $missingDatesData = [];
        $records = $existingRecords->toArray();

        // Get the first date as the anchor start
        $minDate = Carbon::parse($records[0]['date']);
        $maxDate = Carbon::parse(end($records)['date']);

        // Check for gaps in the entire dataset
        for ($i = 0; $i < count($records) - 1; $i++) {
            $currentDate = Carbon::parse($records[$i]['date']);
            $nextDate = Carbon::parse($records[$i + 1]['date']);

            $daysDiff = $currentDate->diffInDays($nextDate);

            // Only fill gaps between existing records
            if ($daysDiff > 1) {
                $startDate = $currentDate->copy()->addDay();

                while ($startDate->lt($nextDate)) {
                    $missingDate = $startDate->toDateString();

                    // Check if this date already exists
                    $exists = RunningTime::where('equipment_number', $equipmentNumber)
                        ->where('date', $missingDate)
                        ->exists();

                    if (!$exists) {
                        // Use the previous record's data, but set running_hours to 0
                        $missingDatesData[] = array_merge($records[$i], [
                            'uuid' => DB::raw('UUID()'),
                            'date' => $missingDate,
                            'date_time' => $startDate->format('Y-m-d H:i:s'),
                            'running_hours' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $startDate->addDay();
                }
            }
        }

        // Bulk insert missing dates
        if (!empty($missingDatesData)) {
            Log::info('RunningTimeProcessor: Filling missing dates for equipment', [
                'equipment_number' => $equipmentNumber,
                'count' => count($missingDatesData),
                'date_range' => [
                    'min' => $minDate->toDateString(),
                    'max' => $maxDate->toDateString(),
                ]
            ]);

            RunningTime::upsert(
                $missingDatesData,
                ['equipment_number', 'date'],
                [
                    'plant_id',
                    'mandt',
                    'point',
                    'date_time',
                    'running_hours',
                    'counter_reading',
                    'maintenance_text',
                    'company_code',
                    'equipment_description',
                    'object_number',
                    'api_created_at',
                    'updated_at'
                ]
            );
        }
    }
}
