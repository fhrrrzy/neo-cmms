<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\RunningTime;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;

class RunningTimeProcessor
{
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
        if (!empty($allowedPlantCodes) && ($plantCode === null || !in_array($plantCode, $allowedPlantCodes, true))) {
            return;
        }
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }
        if (!$plant) {
            return;
        }

        $equipmentNumber = Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR');
        $date = Arr::get($item, 'date') ?? Arr::get($item, 'DATE');
        if (!$equipmentNumber || !$date) {
            throw new Exception("Missing required fields: equipment_number={$equipmentNumber}, date={$date}");
        }

        $apiId = Arr::get($item, 'api_id') ?? Arr::get($item, 'ID');

        $runningTime = null;
        if ($apiId) {
            $runningTime = RunningTime::where('ims_id', $apiId)->first();
        }
        if (!$runningTime) {
            $runningTime = RunningTime::where('equipment_number', $equipmentNumber)
                ->where('date', $date)
                ->first();
        }

        $attributes = [
            'equipment_number' => $equipmentNumber,
            'date' => $date,
            'plant_id' => $plant->id,
            'date_time' => Arr::get($item, 'date_time') ?? Arr::get($item, 'DATE_TIME'),
            'running_hours' => Arr::get($item, 'running_hours') ?? Arr::get($item, 'RECDV'),
            'counter_reading' => Arr::get($item, 'counter_reading') ?? Arr::get($item, 'CNTRR'),
            'maintenance_text' => Arr::get($item, 'maintenance_text') ?? Arr::get($item, 'MDTXT'),
            'company_code' => Arr::get($item, 'company_code') ?? Arr::get($item, 'BUKRS'),
            'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'EQKTU'),
            'object_number' => Arr::get($item, 'object_number') ?? Arr::get($item, 'OBJNR'),
            'api_created_at' => ($ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT')) ? Carbon::parse($ts) : null,
        ];
        if ($apiId) {
            $attributes['ims_id'] = (string) $apiId;
        }

        if ($runningTime) {
            $runningTime->fill($attributes)->save();
        } else {
            RunningTime::create($attributes);
        }
    }
}
