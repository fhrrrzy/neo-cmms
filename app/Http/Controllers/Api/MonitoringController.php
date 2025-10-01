<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Region;
use App\Models\Plant;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function equipment(Request $request)
    {
        $query = Equipment::with(['plant', 'station'])
            ->select([
                'equipment.*',
                'plants.name as plant_name',
                'stations.description as station_description'
            ])
            ->leftJoin('plants', 'equipment.plant_id', '=', 'plants.id')
            ->leftJoin('stations', 'equipment.station_id', '=', 'stations.id');

        // Apply filters
        if ($request->filled('station_id')) {
            $query->where('equipment.station_id', $request->station_id);
        } elseif ($request->filled('plant_id')) {
            $query->where('equipment.plant_id', $request->plant_id);
        } elseif ($request->filled('regional_id')) {
            $query->whereHas('plant', function (Builder $q) use ($request) {
                $q->where('regional_id', $request->regional_id);
            });
        }

        // Get date range for running hours calculation
        $dateStart = $request->get('date_start', now()->subWeek()->toDateString());
        $dateEnd = $request->get('date_end', now()->toDateString());

        // Add running hours calculation
        $query->addSelect([
            'summed_jam_jalan' => DB::table('running_times')
                ->selectRaw('COALESCE(SUM(running_hours), 0)')
                ->whereColumn('running_times.equipment_number', 'equipment.equipment_number')
                ->whereBetween('date', [$dateStart, $dateEnd]),
            'running_times_count' => DB::table('running_times')
                ->selectRaw('COUNT(*)')
                ->whereColumn('running_times.equipment_number', 'equipment.equipment_number')
                ->whereBetween('date', [$dateStart, $dateEnd])
        ]);

        // Use Laravel's built-in pagination
        $perPage = $request->get('per_page', 15);

        $paginatedEquipment = $query->paginate($perPage);

        // Transform the data
        $transformedData = $paginatedEquipment->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'equipment_number' => $item->equipment_number,
                'equipment_description' => $item->equipment_description,
                'company_code' => $item->company_code,
                'object_number' => $item->object_number,
                'point' => $item->point,
                'is_active' => $item->is_active,
                'plant' => $item->plant ? [
                    'id' => $item->plant->id,
                    'name' => $item->plant->name,
                ] : null,
                'station' => $item->station ? [
                    'id' => $item->station->id,
                    'description' => $item->station->description,
                ] : null,
                'summed_jam_jalan' => (float) $item->summed_jam_jalan,
                'running_times_count' => (int) $item->running_times_count,
            ];
        });

        return response()->json([
            'data' => $transformedData,
            'total' => $paginatedEquipment->total(),
            'per_page' => $paginatedEquipment->perPage(),
            'current_page' => $paginatedEquipment->currentPage(),
            'last_page' => $paginatedEquipment->lastPage(),
            'from' => $paginatedEquipment->firstItem(),
            'to' => $paginatedEquipment->lastItem(),
            'has_more_pages' => $paginatedEquipment->hasMorePages(),
        ]);
    }

    public function regions()
    {
        $regions = Region::orderBy('name')->get(['id', 'name']);

        return response()->json($regions);
    }

    public function plants(Request $request)
    {
        $query = Plant::orderBy('name');

        if ($request->filled('regional_id')) {
            $query->where('regional_id', $request->regional_id);
        }

        $plants = $query->get(['id', 'name', 'regional_id']);

        return response()->json($plants);
    }

    public function stations(Request $request)
    {
        $query = Station::orderBy('description');

        if ($request->filled('plant_id')) {
            $query->where('plant_id', $request->plant_id);
        }

        $stations = $query->get(['id', 'description', 'plant_id']);

        return response()->json($stations);
    }
}
