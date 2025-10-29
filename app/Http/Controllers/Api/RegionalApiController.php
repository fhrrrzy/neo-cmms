<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionalApiController extends Controller
{
    /**
     * Get all regions with plant counts
     */
    public function index(Request $request): JsonResponse
    {
        $query = Region::withCount('plants');

        // Apply search filter if provided
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('no', 'like', "%{$search}%");
            });
        }

        $regions = $query->orderBy('no')
            ->get()
            ->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'category' => $region->category,
                    'no' => $region->no,
                    'plants_count' => $region->plants_count,
                ];
            });

        return response()->json([
            'data' => $regions->values(),
        ]);
    }

    /**
     * Get detailed regional data with plants and statistics
     */
    public function show(int $id): JsonResponse
    {
        $region = Region::find($id);

        if (!$region) {
            return response()->json([
                'message' => 'Region not found',
            ], 404);
        }

        // Get plants with equipment counts
        $plants = $region->plants()
            ->withCount('equipment')
            ->orderBy('name')
            ->get()
            ->map(function ($plant) {
                return [
                    'id' => $plant->id,
                    'name' => $plant->name,
                    'plant_code' => $plant->plant_code,
                    'is_active' => $plant->is_active,
                    'equipment_count' => $plant->equipment_count,
                ];
            });

        // Calculate regional statistics
        $totalPlants = $region->plants()->count();
        $activePlants = $region->plants()->where('is_active', true)->count();

        // Total equipment across all plants in this region
        $totalEquipment = $region->plants()
            ->withCount('equipment')
            ->get()
            ->sum('equipment_count');

        // Total work orders across all plants
        $totalWorkOrders = $region->plants()
            ->withCount('workOrders')
            ->get()
            ->sum('work_orders_count');

        return response()->json([
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
                'category' => $region->category,
                'no' => $region->no,
            ],
            'stats' => [
                'total_plants' => $totalPlants,
                'active_plants' => $activePlants,
                'total_equipment' => $totalEquipment,
                'total_work_orders' => $totalWorkOrders,
            ],
            'plants' => $plants,
        ]);
    }
}
