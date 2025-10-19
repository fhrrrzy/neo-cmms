<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\RunningTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class RunningTimeProcessor
{
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        // Debug logging for troubleshooting
        Log::info('RunningTimeProcessor processing item', [
            'item_keys' => array_keys($item),
            'sample_values' => [
                'plant_code' => Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK'),
                'equipment_number' => Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR'),
                'date' => Arr::get($item, 'date') ?? Arr::get($item, 'DATE'),
                'running_hours' => Arr::get($item, 'running_hours') ?? Arr::get($item, 'RECDV'),
            ]
        ]);

        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
        if (!empty($allowedPlantCodes) && ($plantCode === null || !in_array($plantCode, $allowedPlantCodes, true))) {
            Log::warning('RunningTimeProcessor: Plant code not in allowed list', [
                'plant_code' => $plantCode,
                'allowed_codes' => $allowedPlantCodes
            ]);
            return;
        }
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }
        if (!$plant) {
            Log::warning('RunningTimeProcessor: Plant not found', [
                'plant_code' => $plantCode,
                'available_plants' => Plant::pluck('plant_code')->toArray()
            ]);
            return;
        }

        $equipmentNumber = Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR');
        $date = Arr::get($item, 'date') ?? Arr::get($item, 'DATE');
        if (!$equipmentNumber || !$date) {
            Log::error('RunningTimeProcessor: Missing required fields', [
                'equipment_number' => $equipmentNumber,
                'date' => $date,
                'item_data' => $item
            ]);
            throw new Exception("Missing required fields: equipment_number={$equipmentNumber}, date={$date}");
        }

        RunningTime::updateOrCreate(
            [
                'equipment_number' => $equipmentNumber,
                'date' => $date,
            ],
            [
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
                'api_created_at' => ($ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT')) ? Carbon::parse($ts) : null,
            ]
        );
    }
}
