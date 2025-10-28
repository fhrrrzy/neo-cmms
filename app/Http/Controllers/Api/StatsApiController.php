<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentMaterial;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;

class StatsApiController extends Controller
{
    public function overview()
    {
        // Totals via Eloquent models (ORM), with safe fallbacks
        $totalPlants = 0;
        $totalEquipment = 0;
        $totalRegions = 0;
        try {
            $totalPlants = (int) Plant::query()->count();
        } catch (\Throwable $e) {
            $totalPlants = 0;
        }
        try {
            $totalEquipment = (int) Equipment::query()->count();
        } catch (\Throwable $e) {
            $totalEquipment = 0;
        }
        try {
            // Count distinct regional_id referenced by plants
            $totalRegions = (int) Plant::query()
                ->whereNotNull('regional_id')
                ->distinct('regional_id')
                ->count('regional_id');
        } catch (\Throwable $e) {
            $totalRegions = 0;
        }

        // Unique materials per plant: distinct (plant_id, material_number)
        // Unique materials per plant using ORM (distinct pair count)
        $totalMaterialsUniquePerPlant = 0;
        try {
            $totalMaterialsUniquePerPlant = (int) DB::table((new EquipmentMaterial())->getTable())
                ->select(DB::raw('COUNT(DISTINCT plant_id, material_number) as total'))
                ->value('total');
        } catch (\Throwable $e) {
            $totalMaterialsUniquePerPlant = 0;
        }

        return response()->json([
            'total_regions' => (int) $totalRegions,
            'total_plants' => (int) $totalPlants,
            'total_equipment' => (int) $totalEquipment,
            'total_materials_unique_per_plant' => (int) $totalMaterialsUniquePerPlant,
        ]);
    }
}
