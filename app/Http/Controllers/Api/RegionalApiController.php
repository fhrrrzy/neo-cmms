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
                    'uuid' => $region->uuid,
                    'name' => $region->name,
                    'category' => $region->category,
                    'no' => $region->no,
                    'plants_count' => $region->plants_count,
                ];
            });

        return response()->json($regions->values());
    }

    /**
     * Get detailed regional data with plants and statistics
     * Supports both ID (integer) and UUID (string) lookup
     */
    public function show(string $uuid): JsonResponse
    {
        // Only support UUID lookup
        $region = Region::where('uuid', $uuid)->first();
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
                    'uuid' => $plant->uuid,
                    'name' => $plant->name,
                    'plant_code' => $plant->plant_code,
                    'is_active' => $plant->is_active,
                    'equipment_count' => $plant->equipment_count,
                ];
            });

        // Calculate regional statistics
        $totalPlants = $region->plants()->count();
        $activePlants = $region->plants()->where('is_active', true)->count();
        $totalEquipment = $region->plants()->withCount('equipment')->get()->sum('equipment_count');
        $totalWorkOrders = $region->plants()->withCount('workOrders')->get()->sum('work_orders_count');

        return response()->json([
            'region' => [
                'uuid' => $region->uuid,
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
