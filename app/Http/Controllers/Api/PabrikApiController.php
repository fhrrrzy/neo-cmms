<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PabrikApiController extends Controller
{
    /**
     * Get all plants with regional and equipment information
     */
    public function index(Request $request): JsonResponse
    {
        $query = Plant::with('regional')
            ->withCount('equipment');

        // Filter by region if provided
        if ($request->has('regional_id')) {
            $query->where('regional_id', $request->regional_id);
        }

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by plant name or code
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('plant_code', 'like', "%{$search}%");
            });
        }

        $plants = $query->orderBy('name')
            ->get()
            ->map(function ($plant) {
                return [
                    'id' => $plant->id,
                    'name' => $plant->name,
                    'plant_code' => $plant->plant_code,
                    'regional_id' => $plant->regional_id,
                    'regional_name' => $plant->regional->name ?? 'N/A',
                    'is_active' => $plant->is_active,
                    'equipment_count' => $plant->equipment_count,
                    'kaps_terpasang' => $plant->kaps_terpasang,
                ];
            });

        return response()->json([
            'data' => $plants,
        ]);
    }

    /**
     * Get detailed plant data with equipment list and statistics
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $plant = Plant::with('regional')->find($id);

        if (!$plant) {
            return response()->json([
                'message' => 'Plant not found',
            ], 404);
        }

        // Pagination parameters
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'equipment_number');
        $sortDirection = $request->get('sort_direction', 'asc');

        // Build equipment query
        $equipmentQuery = $plant->equipment()
            ->with(['station'])
            ->leftJoin('running_times', function ($join) {
                $join->on('equipment.equipment_number', '=', 'running_times.equipment_number')
                    ->whereRaw('running_times.id IN (SELECT MAX(id) FROM running_times GROUP BY equipment_number)');
            })
            ->select(
                'equipment.*',
                'running_times.counter_reading as latest_running_hours'
            );

        // Apply search filter
        if ($search) {
            $equipmentQuery->where(function ($q) use ($search) {
                $q->where('equipment.equipment_number', 'like', "%{$search}%")
                    ->orWhere('equipment.equipment_description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $equipmentQuery->orderBy($sortBy, $sortDirection);

        // Paginate equipment
        $equipment = $equipmentQuery->paginate($perPage, ['*'], 'page', $page);

        // Format equipment data
        $equipmentData = $equipment->map(function ($item) {
            // Map equipment type from eqtyp code
            $equipmentTypeMap = [
                '1' => 'Mesin Produksi',
                '2' => 'Kendaraan',
                '3' => 'Peralatan Pendukung',
                '4' => 'Instrumen',
            ];

            return [
                'uuid' => $item->uuid,
                'equipment_number' => $item->equipment_number,
                'equipment_description' => $item->equipment_description,
                'station_description' => $item->station->description ?? 'N/A',
                'eqtyp' => $item->eqtyp,
                'equipment_type' => $equipmentTypeMap[$item->eqtyp] ?? 'N/A',
                'latest_running_hours' => $item->latest_running_hours ? number_format($item->latest_running_hours, 1) : 'N/A',
            ];
        });

        // Calculate plant statistics
        $totalEquipment = $plant->equipment()->count();
        $totalStations = $plant->stations()->count();
        $totalWorkOrders = $plant->workOrders()->count();
        $activeWorkOrders = $plant->workOrders()
            ->whereIn('order_status', ['10'])
            ->count();

        return response()->json([
            'plant' => [
                'id' => $plant->id,
                'name' => $plant->name,
                'plant_code' => $plant->plant_code,
                'regional_id' => $plant->regional_id,
                'regional_name' => $plant->regional->name ?? 'N/A',
                'is_active' => $plant->is_active,
                'kaps_terpasang' => $plant->kaps_terpasang,
                'unit' => $plant->unit,
                'instalasi_bunch_press' => $plant->instalasi_bunch_press,
                'cofiring' => $plant->cofiring,
            ],
            'stats' => [
                'total_equipment' => $totalEquipment,
                'total_stations' => $totalStations,
                'total_work_orders' => $totalWorkOrders,
                'active_work_orders' => $activeWorkOrders,
            ],
            'equipment' => [
                'data' => $equipmentData,
                'total' => $equipment->total(),
                'per_page' => $equipment->perPage(),
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
            ],
        ]);
    }
}
