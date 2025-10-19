<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\Station;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use RuntimeException;

class EquipmentProcessor
{
    public function process(array $item): void
    {
        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }
        if (!$plant) {
            throw new RuntimeException('Plant not found: ' . (string) $plantCode);
        }

        $groupName = trim((string) (Arr::get($item, 'group_name') ?? Arr::get($item, 'equipment_group')));
        $equipmentGroup = null;
        if ($groupName !== '') {
            $equipmentGroup = EquipmentGroup::firstOrCreate(['name' => $groupName], [
                'description' => null,
                'is_active' => true,
            ]);
        }

        $station = null;
        $kostl = Arr::get($item, 'cost_center') ?? Arr::get($item, 'KOSTL');
        if ($kostl) {
            $station = Station::where('plant_id', $plant->id)
                ->where('cost_center', $kostl)
                ->first();
        }

        Equipment::updateOrCreate(
            ['equipment_number' => Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR')],
            [
                'plant_id' => $plant->id,
                'station_id' => $station?->id,
                'equipment_group_id' => $equipmentGroup?->id,
                'company_code' => Arr::get($item, 'company_code') ?? Arr::get($item, 'BUKRS'),
                'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'description') ?? Arr::get($item, 'EQKTU'),
                'object_number' => Arr::get($item, 'object_number') ?? Arr::get($item, 'OBJNR'),
                'point' => Arr::get($item, 'point') ?? Arr::get($item, 'POINT'),
                'api_created_at' => ($ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT')) ? Carbon::parse($ts) : null,
                // Complete API field mapping
                'mandt' => Arr::get($item, 'MANDT'),
                'baujj' => Arr::get($item, 'BAUJJ'),
                'groes' => Arr::get($item, 'GROES'),
                'herst' => Arr::get($item, 'HERST'),
                'mrnug' => Arr::get($item, 'MRNGU'),
                'eqtyp' => Arr::get($item, 'EQTYP'),
                'eqart' => Arr::get($item, 'EQART'),
                'maintenance_planner_group' => Arr::get($item, 'MAINTAINANCE_PLANNER_GROUP'),
                'maintenance_work_center' => Arr::get($item, 'MAINTAINANCE_WORK_CENTER'),
                'functional_location' => Arr::get($item, 'FUNCTIONAL_LOCATION'),
                'description_func_location' => Arr::get($item, 'DESCRIPTION_FUNC_LOCATION'),
                'is_active' => true,
            ]
        );
    }
}
