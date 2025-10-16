<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentApiController extends Controller
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
            return response()->json(['message' => 'Equipment not found'], 404);
        }

        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');

        // Running times with pagination & sorting
        $runningTimesQuery = DB::table('running_times')
            ->where('equipment_number', $equipment->equipment_number);

        if ($dateStart && $dateEnd) {
            $runningTimesQuery->whereBetween('date', [$dateStart, $dateEnd]);
        }

        $rtSortBy = in_array($request->get('rt_sort_by'), ['date', 'running_hours', 'counter_reading']) ? $request->get('rt_sort_by') : 'date';
        $rtSortDir = strtolower($request->get('rt_sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $runningTimesQuery->orderBy($rtSortBy, $rtSortDir);

        $rtPerPage = (int) $request->get('rt_per_page', 50);
        $rtPage = (int) $request->get('rt_page', 1);
        $rtTotal = (clone $runningTimesQuery)->count();
        $rtLastPage = (int) ceil($rtTotal / max($rtPerPage, 1));
        $rtFrom = ($rtPage - 1) * $rtPerPage + 1;
        $rtTo = min($rtPage * $rtPerPage, $rtTotal);

        $runningTimes = $runningTimesQuery->forPage($rtPage, $rtPerPage)->get()
            ->map(function ($rt) {
                $format = function ($value) {
                    if ($value === null) return null;
                    $s = (string) $value;
                    if (strpos($s, '.') !== false) {
                        $s = rtrim(rtrim($s, '0'), '.');
                    }
                    return $s;
                };
                // Normalize numeric string formatting without trailing .00
                if (property_exists($rt, 'running_hours')) {
                    $rt->running_hours = $format($rt->running_hours);
                }
                if (property_exists($rt, 'counter_reading')) {
                    $rt->counter_reading = $format($rt->counter_reading);
                }
                return $rt;
            });

        $cumulativeRunningHours = DB::table('running_times')
            ->where('equipment_number', $equipment->equipment_number)
            ->max('counter_reading') ?? 0;

        return response()->json([
            'equipment' => [
                'id' => $equipment->id,
                'equipment_number' => $equipment->equipment_number,
                'equipment_description' => $equipment->equipment_description,
                'company_code' => $equipment->company_code,
                'object_number' => $equipment->object_number,
                'point' => $equipment->point,
                // Additional SAP fields
                'baujj' => $equipment->baujj,
                'groes' => $equipment->groes,
                'herst' => $equipment->herst,
                'mrnug' => $equipment->mrnug,
                'eqart' => $equipment->eqart,
                'description_func_location' => $equipment->description_func_location,
                'functional_location' => $equipment->functional_location,
                'maintenance_planner_group' => $equipment->maintenance_planner_group,
                'maintenance_work_center' => $equipment->maintenance_work_center,
                'eqtyp' => $equipment->eqtyp,
                'equipment_type' => $equipment->equipment_type, // This is the computed attribute
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
                'running_times_pagination' => [
                    'total' => $rtTotal,
                    'per_page' => $rtPerPage,
                    'current_page' => $rtPage,
                    'last_page' => $rtLastPage,
                    'from' => $rtFrom,
                    'to' => $rtTo,
                    'has_more_pages' => $rtPage < $rtLastPage,
                    'sort_by' => $rtSortBy,
                    'sort_direction' => $rtSortDir,
                ],
            ],
        ]);
    }
}
