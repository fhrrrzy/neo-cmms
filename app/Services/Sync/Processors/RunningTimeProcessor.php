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
     * Normalize api_created_at to MySQL datetime format
     * Handles Carbon objects, ISO strings, and MySQL datetime strings
     */
    private function normalizeApiCreatedAt($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'format')) {
            // Carbon object
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            try {
                // If it's ISO format (contains T and Z)
                if (strpos($value, 'T') !== false && strpos($value, 'Z') !== false) {
                    return Carbon::parse($value)->format('Y-m-d H:i:s');
                }
                // If it's already MySQL datetime format, return as is
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?$/', $value)) {
                    return $value;
                }
                // Try parsing as any date and converting to MySQL format
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

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
                // Parse and format to MySQL datetime format
                $apiCreatedAt = Carbon::parse($ts)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // If it's already in MySQL format, use it as is
                if (is_string($ts) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ts)) {
                    $apiCreatedAt = $ts;
                } else {
                    // Skip invalid dates
                }
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
     * Only fills gaps within the date range of the currently synced data
     */
    private function fillMissingDates(array $insertedData): void
    {
        if (empty($insertedData)) {
            return;
        }

        // Get the date range from the inserted data (the current sync batch)
        $dates = array_column($insertedData, 'date');
        $minDate = min($dates);
        $maxDate = max($dates);

        $uniqueEquipmentNumbers = array_unique(array_column($insertedData, 'equipment_number'));

        // Only fill gaps within the current sync date range
        $this->fillGapsForMultipleEquipment($uniqueEquipmentNumbers, $minDate, $maxDate);
    }

    /**
     * Fill gaps for multiple equipment in batch - much faster than individual queries
     * Processes equipment in chunks to avoid memory issues with large datasets
     */
    private function fillGapsForMultipleEquipment(array $equipmentNumbers, string $minDate, string $maxDate): void
    {
        if (empty($equipmentNumbers)) {
            return;
        }

        // Process equipment in chunks to avoid loading too much data into memory
        $equipmentChunks = array_chunk($equipmentNumbers, 500); // Process 500 equipment at a time

        foreach ($equipmentChunks as $chunk) {
            $this->fillGapsForEquipmentChunk($chunk, $minDate, $maxDate);
        }
    }

    /**
     * Fill gaps for a chunk of equipment
     * Only fills gaps within the specified date range (the current sync batch range)
     */
    private function fillGapsForEquipmentChunk(array $equipmentNumbers, string $minDate, string $maxDate): void
    {
        // Only fetch records within the current sync date range
        // This prevents filling gaps from historical data outside the current sync
        $allRecords = RunningTime::whereIn('equipment_number', $equipmentNumbers)
            ->whereBetween('date', [$minDate, $maxDate])
            ->orderBy('date')
            ->get()
            ->groupBy('equipment_number');

        $allMissingDatesData = [];

        foreach ($equipmentNumbers as $equipmentNumber) {
            $existingRecords = $allRecords->get($equipmentNumber);

            if (!$existingRecords || $existingRecords->isEmpty()) {
                continue;
            }

            $records = $existingRecords->values()->toArray();
            $missingDatesData = [];

            // Check for gaps in the dataset - only within the sync date range
            $syncedMinDate = Carbon::parse($minDate);
            $syncedMaxDate = Carbon::parse($maxDate);

            for ($i = 0; $i < count($records) - 1; $i++) {
                $currentDate = Carbon::parse($records[$i]['date']);
                $nextDate = Carbon::parse($records[$i + 1]['date']);

                // Only fill gaps if both dates are within the sync range
                if ($currentDate->lt($syncedMinDate) || $nextDate->gt($syncedMaxDate)) {
                    continue;
                }

                $daysDiff = $currentDate->diffInDays($nextDate);

                // Only fill gaps between existing records
                // Limit gap size to avoid filling entire years of missing data
                if ($daysDiff > 1 && $daysDiff <= 30) {
                    $startDate = $currentDate->copy()->addDay();

                    while ($startDate->lt($nextDate)) {
                        // Ensure we don't fill dates outside the sync range
                        if ($startDate->lt($syncedMinDate) || $startDate->gt($syncedMaxDate)) {
                            $startDate->addDay();
                            continue;
                        }

                        $missingDate = $startDate->toDateString();

                        // Use the previous record's data, but set running_hours to 0
                        // Only copy specific fields to avoid issues with array data from database records
                        $missingDatesData[] = [
                            'uuid' => DB::raw('UUID()'),
                            'equipment_number' => $records[$i]['equipment_number'],
                            'date' => $missingDate,
                            'plant_id' => $records[$i]['plant_id'],
                            'mandt' => $records[$i]['mandt'],
                            'point' => $records[$i]['point'],
                            'date_time' => $startDate->format('Y-m-d H:i:s'),
                            'running_hours' => 0,
                            'counter_reading' => $records[$i]['counter_reading'],
                            'maintenance_text' => $records[$i]['maintenance_text'],
                            'company_code' => $records[$i]['company_code'],
                            'equipment_description' => $records[$i]['equipment_description'],
                            'object_number' => $records[$i]['object_number'],
                            'api_created_at' => $this->normalizeApiCreatedAt($records[$i]['api_created_at']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $startDate->addDay();
                    }
                }
            }

            // Collect all missing dates data for bulk insert
            if (!empty($missingDatesData)) {
                $allMissingDatesData = array_merge($allMissingDatesData, $missingDatesData);

                Log::info('RunningTimeProcessor: Filling missing dates for equipment', [
                    'equipment_number' => $equipmentNumber,
                    'count' => count($missingDatesData),
                ]);
            }
        }

        // Bulk insert all missing dates in a single operation
        if (!empty($allMissingDatesData)) {
            // Check which dates already exist to avoid duplicates
            $existingDates = RunningTime::whereIn('equipment_number', $equipmentNumbers)
                ->whereIn('date', array_column($allMissingDatesData, 'date'))
                ->select(['equipment_number', 'date'])
                ->get()
                ->map(function ($record) {
                    return $record->equipment_number . '-' . $record->date;
                })
                ->toArray();

            // Filter out dates that already exist
            $filteredMissingDates = array_filter($allMissingDatesData, function ($item) use ($existingDates) {
                return !in_array($item['equipment_number'] . '-' . $item['date'], $existingDates);
            });

            if (!empty($filteredMissingDates)) {
                RunningTime::upsert(
                    $filteredMissingDates,
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
}
