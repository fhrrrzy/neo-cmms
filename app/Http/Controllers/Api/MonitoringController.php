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
                'stations.description as station_description',
                'equipment.description_func_location'
            ])
            ->leftJoin('plants', 'equipment.plant_id', '=', 'plants.id')
            ->leftJoin('stations', 'equipment.station_id', '=', 'stations.id');

        // Apply filters (support both single and multiple selections)
        if ($request->filled('station_codes')) {
            $stationCodes = is_array($request->station_codes) ? $request->station_codes : [$request->station_codes];

            // If we have station codes, we need to filter by plant + station code combination
            if ($request->filled('plant_ids')) {
                $plantIds = is_array($request->plant_ids) ? $request->plant_ids : [$request->plant_ids];
                $query->whereIn('equipment.plant_id', $plantIds);

                // Filter stations by cost_center (plant_code + station_code)
                $query->whereHas('station', function (Builder $q) use ($stationCodes, $plantIds) {
                    $q->where(function (Builder $subQ) use ($stationCodes, $plantIds) {
                        foreach ($plantIds as $plantId) {
                            $plant = Plant::find($plantId);
                            if ($plant) {
                                foreach ($stationCodes as $stationCode) {
                                    $subQ->orWhere('cost_center', $plant->plant_code . $stationCode);
                                }
                            }
                        }
                    });
                });
            } else {
                // If no plant filter, show all stations with these codes across all plants
                $query->whereHas('station', function (Builder $q) use ($stationCodes) {
                    $q->where(function (Builder $subQ) use ($stationCodes) {
                        foreach ($stationCodes as $stationCode) {
                            $subQ->orWhere('cost_center', 'like', '%' . $stationCode);
                        }
                    });
                });
            }
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
            $like = "%{$search}%";
            $query->whereAny([
                'equipment.equipment_number',
                'equipment.equipment_description',
                'plants.name',
                'stations.description',
            ], 'like', $like);
        }

        // Apply strict filtering when all conditions must match
        if ($request->filled('strict_filter')) {
            $strictTerm = trim($request->get('strict_filter'));
            $strictLike = "%{$strictTerm}%";
            $query->whereAll([
                'equipment.equipment_number',
                'equipment.equipment_description',
            ], 'like', $strictLike);
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

        // Add running hours calculation and biaya calculation using subqueries
        $query->addSelect([
            'running_times_count' => DB::table('running_times')
                ->selectRaw('COALESCE(SUM(running_hours), 0)')
                ->whereColumn('running_times.equipment_number', 'equipment.equipment_number')
                ->whereBetween('date', [$dateStart, $dateEnd]),
            'cumulative_jam_jalan' => DB::table('running_times')
                ->selectRaw('COALESCE(MAX(counter_reading), 0)')
                ->whereColumn('running_times.equipment_number', 'equipment.equipment_number'),
            'biaya' => DB::table('equipment_work_orders')
                ->selectRaw('COALESCE(SUM(value_withdrawn), 0)')
                ->whereColumn('equipment_work_orders.equipment_number', 'equipment.equipment_number')
                ->whereBetween('requirements_date', [$dateStart, $dateEnd]),
            'active_work_orders_count' => DB::table('work_orders')
                ->selectRaw('COUNT(*)')
                ->whereColumn('work_orders.equipment_number', 'equipment.equipment_number')
                ->where('work_orders.order_status', '!=', 'COMPLETED')
        ]);

        // Use subquery where clause for equipment with recent activity
        if ($request->filled('has_recent_activity')) {
            $query->whereExists(function ($q) use ($dateStart, $dateEnd) {
                $q->select(DB::raw(1))
                    ->from('running_times')
                    ->whereColumn('running_times.equipment_number', 'equipment.equipment_number')
                    ->whereBetween('date', [$dateStart, $dateEnd])
                    ->where('running_hours', '>', 0);
            });
        }

        // Apply sorting
        switch ($sortBy) {
            case 'equipment_number':
                $query->orderBy('equipment.equipment_number', $sortDirection);
                break;
            case 'equipment_description':
                $query->orderBy('equipment.equipment_description', $sortDirection);
                break;
            case 'plant.name':
                $query->orderBy('plants.name', $sortDirection);
                break;
            case 'station.description':
                $query->orderBy('stations.description', $sortDirection);
                break;
            case 'cumulative_jam_jalan':
                $query->orderByRaw('cumulative_jam_jalan ' . $sortDirection);
                break;
            case 'running_times_count':
                $query->orderByRaw('running_times_count ' . $sortDirection);
                break;
            case 'functional_location':
                $query->orderBy('equipment.description_func_location', $sortDirection);
                break;
            case 'biaya':
                $query->orderByRaw('biaya ' . $sortDirection);
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
                'functional_location' => $item->description_func_location,
                'biaya' => (float) $item->biaya,
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
                'station_codes' => $request->get('station_codes'),
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

    public function biaya(Request $request)
    {
        // Validate required equipment_number
        if (!$request->filled('equipment_number')) {
            return response()->json([
                'message' => 'Equipment number is required',
                'error' => 'Missing equipment_number parameter'
            ], 400);
        }

        $equipmentNumber = $request->get('equipment_number');

        // Get date range (default to last 7 days if not provided)
        $dateStart = $request->get('date_start', now()->subWeek()->toDateString());
        $dateEnd = $request->get('date_end', now()->toDateString());

        // Query equipment work orders for biaya data
        $query = DB::table('equipment_work_orders')
            ->select([
                'equipment_work_orders.*',
                'work_orders.order_type',
                'work_orders.description as order_description',
                'work_orders.order_status',
                'work_orders.cause_text',
                'work_orders.item_text',
                'plants.name as plant_name',
            ])
            ->leftJoin('work_orders', 'equipment_work_orders.order_number', '=', 'work_orders.order')
            ->leftJoin('plants', 'equipment_work_orders.plant_id', '=', 'plants.id')
            ->where('equipment_work_orders.equipment_number', $equipmentNumber)
            ->whereBetween('equipment_work_orders.requirements_date', [$dateStart, $dateEnd])
            ->where('equipment_work_orders.value_withdrawn', '>', 0) // Only show records with actual value
            ->orderBy('equipment_work_orders.requirements_date', 'desc');

        // Apply search filter using whereAny
        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $like = "%{$search}%";
            $query->whereAny([
                'equipment_work_orders.order_number',
                'equipment_work_orders.material',
                'equipment_work_orders.material_description',
                'work_orders.description',
            ], 'like', $like);
        }

        // Apply material filter
        if ($request->filled('material')) {
            $query->where('equipment_work_orders.material', $request->material);
        }

        // Apply order number filter
        if ($request->filled('order_number')) {
            $query->where('equipment_work_orders.order_number', $request->order_number);
        }

        // Handle sorting
        $sortBy = $request->get('sort_by', 'requirements_date');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Map sort fields
        $allowedSorts = [
            'requirements_date' => 'equipment_work_orders.requirements_date',
            'order_number' => 'equipment_work_orders.order_number',
            'material' => 'equipment_work_orders.material',
            'material_description' => 'equipment_work_orders.material_description',
            'quantity_withdrawn' => 'equipment_work_orders.quantity_withdrawn',
            'value_withdrawn' => 'equipment_work_orders.value_withdrawn',
            'order_type' => 'work_orders.order_type',
            'order_status' => 'work_orders.order_status',
        ];

        if (isset($allowedSorts[$sortBy])) {
            $query->orderBy($allowedSorts[$sortBy], $sortDirection);
        } else {
            $query->orderBy('equipment_work_orders.requirements_date', 'desc');
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);

        // Get total count before pagination
        $total = (clone $query)->count();

        // Apply pagination
        $biayaData = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Transform the data
        $transformedData = $biayaData->map(function ($item) {
            return [
                'id' => $item->id,
                'order_number' => $item->order_number,
                'equipment_number' => $item->equipment_number,
                'requirements_date' => $item->requirements_date,
                'material' => $item->material,
                'material_description' => $item->material_description,
                'requirement_quantity' => $item->requirement_quantity ? (float) $item->requirement_quantity : null,
                'quantity_withdrawn' => $item->quantity_withdrawn ? (float) $item->quantity_withdrawn : null,
                'value_withdrawn' => $item->value_withdrawn ? (float) $item->value_withdrawn : null,
                'currency' => $item->currency,
                'base_unit_of_measure' => $item->base_unit_of_measure,
                'reservation' => $item->reservation,
                'reservation_status' => $item->reservation_status,
                'movement_type' => $item->movement_type,
                'plant_name' => $item->plant_name,
                'order_type' => $item->order_type,
                'order_description' => $item->order_description,
                'order_status' => $item->order_status,
                'cause_text' => $item->cause_text,
                'item_text' => $item->item_text,
            ];
        });

        $lastPage = (int) ceil($total / max($perPage, 1));

        return response()->json([
            'data' => $transformedData,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $page > 1 ? (($page - 1) * $perPage) + 1 : ($total > 0 ? 1 : 0),
            'to' => min($page * $perPage, $total),
            'filters' => [
                'equipment_number' => $equipmentNumber,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'search' => $request->get('search'),
                'material' => $request->get('material'),
                'order_number' => $request->get('order_number'),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }
}
