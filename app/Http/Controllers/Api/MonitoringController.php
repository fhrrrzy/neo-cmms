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
use Illuminate\Support\Facades\Storage;

class MonitoringController extends Controller
{
    public function equipment(Request $request)
    {
        $query = Equipment::with([
                'plant',
                'station',
                'images' => function ($q) {
                    $q->select('id', 'equipment_number', 'filepath', 'name')
                        ->latest('id')
                        ->limit(1);
                },
            ])
            ->select([
                'equipment.*',
                'plants.name as plant_name',
                'stations.description as station_description',
                'equipment.description_func_location'
            ])
            ->leftJoin('plants', 'equipment.plant_id', '=', 'plants.id')
            ->leftJoin('stations', 'equipment.station_id', '=', 'stations.id');

        // Apply filters (support both single and multiple selections)
        // First, determine which plant IDs we're filtering by
        $plantIds = [];
        if ($request->filled('plant_uuid')) {
            $plant = Plant::where('uuid', $request->plant_uuid)->first();
            if ($plant) {
                $plantIds = [$plant->id];
                $query->where('equipment.plant_id', $plant->id);
            }
        } elseif ($request->filled('plant_uuids')) {
            $plantUuids = is_array($request->plant_uuids) ? $request->plant_uuids : [$request->plant_uuids];
            $plants = Plant::whereIn('uuid', $plantUuids)->get();
            $plantIds = $plants->pluck('id')->toArray();
            $query->whereIn('equipment.plant_id', $plantIds);
        }

        // Apply station filter (works with or without plant filter)
        if ($request->filled('station_codes')) {
            $stationCodes = is_array($request->station_codes) ? $request->station_codes : [$request->station_codes];

            if (!empty($plantIds)) {
                // If plant filter exists, filter stations by cost_center (plant_code + station_code)
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
        }

        // Apply regional filter (only if no plant filter exists)
        if (empty($plantIds) && $request->filled('regional_uuid')) {
            $region = Region::where('uuid', $request->regional_uuid)->first();
            if ($region) {
                $query->whereHas('plant', function (Builder $q) use ($region) {
                    $q->where('regional_id', $region->id);
                });
            }
        }

        // Apply equipment type filter
        if ($request->filled('equipment_types')) {
            $equipmentTypes = is_array($request->equipment_types) ? $request->equipment_types : [$request->equipment_types];

            // Cast to strings since eqtyp is VARCHAR in database
            $stringTypes = array_map('strval', $equipmentTypes);
            $query->whereIn('equipment.eqtyp', $stringTypes);
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
                'uuid' => $item->uuid,
                'equipment_number' => $item->equipment_number,
                'equipment_description' => $item->equipment_description,
                'company_code' => $item->company_code,
                'object_number' => $item->object_number,
                'point' => $item->point,
                'equipment_type' => $item->equipment_type,
                'plant' => $item->plant ? [
                    'uuid' => $item->plant->uuid,
                    'name' => $item->plant->name,
                ] : null,
                'station' => $item->station ? [
                    'id' => $item->station->id,
                    'description' => $item->station->description,
                ] : null,
                'images' => $item->images ? $item->images->map(function ($img) {
                    return [
                        'url' => $img->filepath ? Storage::url($img->filepath) : null,
                        'name' => $img->name,
                    ];
                })->filter(fn($i) => !empty($i['url']))->values() : [],
                'running_times_count' => (int) ($item->running_times_count ?? 0),
                'cumulative_jam_jalan' => (float) ($item->cumulative_jam_jalan ?? 0),
                'functional_location' => $item->description_func_location,
                'biaya' => (float) ($item->biaya ?? 0),
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
                'regional_uuid' => $request->get('regional_uuid'),
                'plant_uuid' => $request->get('plant_uuid'),
                'plant_uuids' => $request->get('plant_uuids'),
                'station_codes' => $request->get('station_codes'),
                'equipment_types' => $request->get('equipment_types'),
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
        $regions = Region::orderBy('name')->get(['uuid', 'name']);
        return response()->json($regions);
    }

    public function plants(Request $request)
    {
        $query = Plant::orderBy('name');
        if ($request->filled('regional_uuid')) {
            $region = Region::where('uuid', $request->regional_uuid)->first();
            if ($region) {
                $query->where('regional_id', $region->id);
            }
        }
        $plants = $query->with('region')->get();
        // Return regional_uuid for each plant
        $result = $plants->map(function ($plant) {
            return [
                'uuid' => $plant->uuid,
                'name' => $plant->name,
                'regional_uuid' => optional($plant->region)->uuid,
            ];
        });
        return response()->json($result);
    }

    public function stations(Request $request)
    {
        $query = Station::orderBy('description');

        if ($request->filled('plant_uuid')) {
            $plant = Plant::where('uuid', $request->plant_uuid)->first();
            if ($plant) {
                $query->where('plant_id', $plant->id);
            }
        }

        $stations = $query->get(['id', 'description', 'plant_id']);

        return response()->json($stations);
    }

    public function jamJalanSummary(Request $request)
    {
        $dateStart = $request->get('date_start', now()->subWeek()->toDateString());
        $dateEnd = $request->get('date_end', now()->toDateString());

        // Build query for plants with optional filters
        $plantQuery = Plant::query();
        if ($request->filled('regional_uuid')) {
            $region = Region::where('uuid', $request->regional_uuid)->first();
            if ($region) {
                $plantQuery->where('regional_id', $region->id);
            }
        }
        if ($request->filled('plant_uuids')) {
            $plantUuids = is_array($request->plant_uuids) ? $request->plant_uuids : [$request->plant_uuids];
            $plantQuery->whereIn('uuid', $plantUuids);
        }
        $plants = $plantQuery->orderBy('name')->get();
        $plantIdToUuid = $plants->mapWithKeys(fn($p) => [$p->id => $p->uuid]);
        $plantUuidToId = $plants->mapWithKeys(fn($p) => [$p->uuid => $p->id]);

        // Get all dates in the range
        $dates = collect();
        $currentDate = \Carbon\Carbon::parse($dateStart);
        $endDate = \Carbon\Carbon::parse($dateEnd);
        while ($currentDate->lte($endDate)) {
            $dates->push($currentDate->format('Y-m-d'));
            $currentDate->addDay();
        }

        // Get daily plant data for the date range (by plant_id)
        $dailyPlantDataMap = [];
        $dailyPlantDataRecords = DB::table('daily_plant_data')
            ->whereBetween('date', [$dateStart, $dateEnd])
            ->get();
        foreach ($dailyPlantDataRecords as $record) {
            $key = $record->plant_id . '_' . $record->date;
            $dailyPlantDataMap[$key] = $record;
        }
        // Get equipment counts -- running_times by plant_id
        $equipmentCounts = DB::table('running_times')
            ->select('running_times.plant_id', 'running_times.date', DB::raw('COUNT(DISTINCT running_times.equipment_number) as equipment_count'))
            ->whereBetween('running_times.date', [$dateStart, $dateEnd])
            ->whereRaw('COALESCE(running_times.running_hours, 0) > 0')
            ->groupBy('running_times.plant_id', 'running_times.date')
            ->get()
            ->keyBy(function ($item) {
                return $item->plant_id . '_' . $item->date;
            });
        // Build summary data (return uuid as public key)
        $summaryData = [];
        foreach ($plants as $plant) {
            $plantData = [
                'uuid' => $plant->uuid,
                'name' => $plant->name,
                'dates' => [],
            ];
            foreach ($dates as $date) {
                $key = $plant->id . '_' . $date;
                $equipmentCount = isset($equipmentCounts[$key]) ? $equipmentCounts[$key]->equipment_count : 0;
                // Get is_mengolah status
                $isMengolah = true;
                if (isset($dailyPlantDataMap[$key])) {
                    $dailyData = $dailyPlantDataMap[$key];
                    $isMengolah = $dailyData->is_mengolah != 0;
                }
                $plantData['dates'][$date] = [
                    'count' => $equipmentCount,
                    'is_mengolah' => $isMengolah,
                ];
            }
            $summaryData[] = $plantData;
        }
        return response()->json([
            'data' => $summaryData,
            'dates' => $dates->values()->all(),
            'filters' => [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'regional_uuid' => $request->get('regional_uuid'),
                'plant_uuids' => $request->get('plant_uuids'),
            ],
        ]);
    }

    public function jamJalanDetail(Request $request)
    {
        $plantUuid = $request->get('plant_uuid');
        $date = $request->get('date');

        if (!$plantUuid || !$date) {
            return response()->json([
                'message' => 'Plant UUID and date are required',
                'error' => 'Missing required parameters'
            ], 400);
        }

        // Convert plant_uuid → plant_id
        $plant = \App\Models\Plant::where('uuid', $plantUuid)->first();
        if (!$plant) {
            return response()->json([
                'message' => 'Plant not found',
                'error' => 'Invalid plant_uuid supplied'
            ], 404);
        }
        $plantId = $plant->id;

        // Get is_mengolah status from daily_plant_data
        $dailyPlantData = DB::table('daily_plant_data')
            ->where('plant_id', $plantId)
            ->where('date', $date)
            ->select('is_mengolah')
            ->first();
        $isMengolah = $dailyPlantData ? ($dailyPlantData->is_mengolah != 0) : true;
        // Get equipment with running times for that date
        $equipmentWithRunningTime = DB::table('running_times')
            ->join('equipment', 'running_times.equipment_number', '=', 'equipment.equipment_number')
            ->where('running_times.plant_id', $plantId)
            ->where('running_times.date', $date)
            ->whereRaw('COALESCE(running_times.running_hours, 0) > 0')
            ->select([
                'equipment.uuid',
                'equipment.equipment_number',
                'equipment.equipment_description',
                'running_times.running_hours',
                'running_times.counter_reading',
            ])
            ->orderBy('equipment.equipment_number')
            ->get();
        // Get equipment without running times (0 or null)
        $equipmentWithoutRunningTime = DB::table('equipment')
            ->where('plant_id', $plantId)
            ->whereNotExists(function ($query) use ($plantId, $date) {
                $query->select(DB::raw(1))
                    ->from('running_times')
                    ->whereRaw('running_times.equipment_number = equipment.equipment_number')
                    ->where('running_times.plant_id', $plantId)
                    ->where('running_times.date', $date)
                    ->whereRaw('COALESCE(running_times.running_hours, 0) > 0');
            })
            ->select([
                'equipment.uuid',
                'equipment.equipment_number',
                'equipment.equipment_description',
            ])
            ->orderBy('equipment.equipment_number')
            ->get();
        return response()->json([
            'is_mengolah' => $isMengolah,
            'with_running_time' => $equipmentWithRunningTime,
            'without_running_time' => $equipmentWithoutRunningTime,
        ]);
    }

    public function biaya(Request $request)
    {
        // Validate required equipment_uuid
        if (!$request->filled('equipment_uuid')) {
            return response()->json([
                'message' => 'Equipment UUID is required',
                'error' => 'Missing equipment_uuid parameter'
            ], 400);
        }

        $equipmentUuid = $request->get('equipment_uuid');

        // Get equipment to retrieve equipment_number
        $equipment = Equipment::where('uuid', $equipmentUuid)->first();

        if (!$equipment) {
            return response()->json([
                'message' => 'Equipment not found',
                'error' => 'Invalid equipment UUID'
            ], 404);
        }

        $equipmentNumber = $equipment->equipment_number;

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
                'equipment_uuid' => $equipmentUuid,
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
