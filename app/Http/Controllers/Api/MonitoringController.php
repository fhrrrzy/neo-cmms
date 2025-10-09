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

        // Apply filters (support both single and multiple selections)
        if ($request->filled('station_ids')) {
            $stationIds = is_array($request->station_ids) ? $request->station_ids : [$request->station_ids];
            $query->whereIn('equipment.station_id', $stationIds);
        } elseif ($request->filled('plant_ids')) {
            $plantIds = is_array($request->plant_ids) ? $request->plant_ids : [$request->plant_ids];
            $query->whereIn('equipment.plant_id', $plantIds);
        } elseif ($request->filled('regional_ids')) {
            $regionalIds = is_array($request->regional_ids) ? $request->regional_ids : [$request->regional_ids];
            $query->whereHas('plant', function (Builder $q) use ($regionalIds) {
                $q->whereIn('regional_id', $regionalIds);
            });
        }

        // Apply search across key fields
        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function (Builder $q) use ($search) {
                $like = "%{$search}%";
                $q->where('equipment.equipment_number', 'like', $like)
                    ->orWhere('equipment.equipment_description', 'like', $like)
                    ->orWhere('plants.name', 'like', $like)
                    ->orWhere('stations.description', 'like', $like);
            });
        }

        // Get date range for running hours calculation
        $dateStart = $request->get('date_start', now()->subWeek()->toDateString());
        $dateEnd = $request->get('date_end', now()->toDateString());

        // Handle sorting
        $sortBy = $request->get('sort_by', 'equipment_number');
        $sortDirection = $request->get('sort_direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Add running hours calculation
        $query->addSelect([
            'running_times_count' => DB::table('running_times')
                ->selectRaw('COUNT(*)')
                ->whereColumn('running_times.equipment_number', 'equipment.equipment_number')
                ->whereBetween('date', [$dateStart, $dateEnd]),
            'cumulative_jam_jalan' => DB::table('running_times')
                ->selectRaw('COALESCE(MAX(counter_reading), 0)')
                ->whereColumn('running_times.equipment_number', 'equipment.equipment_number')
        ]);

        // Apply sorting
        switch ($sortBy) {
            case 'equipment_number':
                $query->orderBy('equipment.equipment_number', $sortDirection);
                break;
            case 'equipment_description':
                $query->orderBy('equipment.equipment_description', $sortDirection);
                break;
            case 'plant_name':
                $query->orderBy('plants.name', $sortDirection);
                break;
            case 'station_description':
                $query->orderBy('stations.description', $sortDirection);
                break;
            case 'cumulative_jam_jalan':
                $query->orderBy('cumulative_jam_jalan', $sortDirection);
                break;
            case 'running_times_count':
                $query->orderBy('running_times_count', $sortDirection);
                break;
            default:
                $query->orderBy('equipment.equipment_number', 'asc');
                break;
        }

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
                'equipment_type' => $item->equipment_type,
                'plant' => $item->plant ? [
                    'id' => $item->plant->id,
                    'name' => $item->plant->name,
                ] : null,
                'station' => $item->station ? [
                    'id' => $item->station->id,
                    'description' => $item->station->description,
                ] : null,
                'running_times_count' => (int) $item->running_times_count,
                'cumulative_jam_jalan' => (float) $item->cumulative_jam_jalan,
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
            'filters' => [
                'regional_ids' => $request->get('regional_ids'),
                'plant_ids' => $request->get('plant_ids'),
                'station_ids' => $request->get('station_ids'),
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'search' => $request->get('search'),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
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

    // equipmentDetail method removed - now handled in web routes with Inertia
}
