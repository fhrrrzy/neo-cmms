<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentWorkOrder;
use Illuminate\Http\Request;

class EquipmentWorkOrderApiController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipmentWorkOrder::query();

        if ($request->filled('equipment_number')) {
            $query->where('equipment_number', $request->equipment_number);
        }
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('requirements_date', [$request->date_start, $request->date_end]);
        }

        // Sorting
        $allowedSorts = [
            'requirements_date' => 'requirements_date',
            'order_number' => 'order_number',
            'material' => 'material',
            'requirement_quantity' => 'requirement_quantity',
            'quantity_withdrawn' => 'quantity_withdrawn',
            'value_withdrawn' => 'value_withdrawn',
        ];
        $sortBy = $request->get('sort_by');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sortBy && isset($allowedSorts[$sortBy])) {
            $query->orderBy($allowedSorts[$sortBy], $sortDirection);
        } else {
            $query->orderBy('requirements_date', 'desc');
        }

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => array_map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'requirements_date' => $item['requirements_date'] ?? null,
                    'order_number' => $item['order_number'] ?? null,
                    'reservation' => $item['reservation'] ?? null,
                    'material' => $item['material'] ?? null,
                    'material_description' => $item['material_description'] ?? null,
                    'requirement_quantity' => $item['requirement_quantity'] ?? null,
                    'base_unit_of_measure' => $item['base_unit_of_measure'] ?? null,
                    'quantity_withdrawn' => $item['quantity_withdrawn'] ?? null,
                    'value_withdrawn' => $item['value_withdrawn'] ?? null,
                    'currency' => $item['currency'] ?? null,
                ];
            }, $paginated->items()),
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }
}
