<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EquipmentSearchApiController extends Controller
{
    /**
     * Search equipment by equipment number or UUID
     * Results are cached for 5 minutes to improve performance
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->get('query');
        $limit = $request->get('limit', 10);

        // Create cache key based on search query and limit
        $cacheKey = "equipment_search:{$query}:{$limit}";

        // Cache results for 5 minutes (300 seconds)
        $results = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            return Equipment::with(['plant.regional', 'station'])
                ->where(function ($q) use ($query) {
                    $q->where('equipment_number', 'LIKE', "%{$query}%")
                        ->orWhere('uuid', 'LIKE', "%{$query}%")
                        ->orWhere('equipment_description', 'LIKE', "%{$query}%");
                })
                ->select([
                    'id',
                    'uuid',
                    'equipment_number',
                    'equipment_description',
                    'plant_id',
                    'station_id',
                ])
                ->limit($limit)
                ->get()
                ->map(function ($equipment) {
                    return [
                        'id' => $equipment->id,
                        'uuid' => $equipment->uuid,
                        'equipment_number' => $equipment->equipment_number,
                        'equipment_description' => $equipment->equipment_description,
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
                    ];
                });
        });

        return response()->json([
            'data' => $results,
            'query' => $query,
            'limit' => $limit,
            'count' => $results->count(),
        ]);
    }
}
