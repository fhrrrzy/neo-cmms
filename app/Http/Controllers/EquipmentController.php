<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function show(string $equipmentNumber, Request $request)
    {
        $equipment = Equipment::with(['plant.regional', 'station'])
            ->select([
                'equipment.*',
                'plants.name as plant_name',
                'stations.description as station_description',
            ])
            ->leftJoin('plants', 'equipment.plant_id', '=', 'plants.id')
            ->leftJoin('stations', 'equipment.station_id', '=', 'stations.id')
            ->where('equipment.equipment_number', $equipmentNumber)
            ->first();

        if (!$equipment) {
            abort(404, 'Equipment not found');
        }

        $defaultStart = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $defaultEnd = now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d');
        $dateStart = $request->get('date_start', $defaultStart);
        $dateEnd = $request->get('date_end', $defaultEnd);

        $runningTimes = DB::table('running_times')
            ->where('equipment_number', $equipment->equipment_number)
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->orderBy('date', 'asc')
            ->get();

        $cumulativeRunningHours = DB::table('running_times')
            ->where('equipment_number', $equipment->equipment_number)
            ->max('counter_reading') ?? 0;

        $equipmentData = [
            'id' => $equipment->id,
            'equipment_number' => $equipment->equipment_number,
            'equipment_description' => $equipment->equipment_description,
            'company_code' => $equipment->company_code,
            'object_number' => $equipment->object_number,
            'point' => $equipment->point,
            'plant' => $equipment->plant ? [
                'id' => $equipment->plant->id,
                'name' => $equipment->plant->name,
                'regional' => $equipment->plant->regional ? [
                    'id' => $equipment->plant->regional->id,
                    'name' => $equipment->plant->regional->name,
                ] : null,
            ] : null,
            'station' => $equipment->station ? [
                'id' => $equipment->station->id,
                'description' => $equipment->station->description,
            ] : null,
            'cumulative_running_hours' => (float) $cumulativeRunningHours,
            'recent_running_times' => $runningTimes,
            'date_range' => [
                'start' => $dateStart,
                'end' => $dateEnd,
            ],
        ];

        return Inertia::render('equipment/detail/detail', [
            'equipmentNumber' => $equipmentNumber,
            'equipment' => $equipmentData,
        ]);
    }
}
