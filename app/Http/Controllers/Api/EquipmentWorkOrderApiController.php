<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentWorkOrderMaterial;
use Illuminate\Http\Request;

class EquipmentWorkOrderApiController extends Controller
{
    public function index(Request $request)
    {
        // Grouped-by-material mode
        if ($request->get('group_by') === 'material') {
            $query = EquipmentWorkOrderMaterial::query();

            if ($request->filled('equipment_number')) {
                $query->where('equipment_number', $request->equipment_number);
            }
            if ($request->filled('date_start') && $request->filled('date_end')) {
                $query->whereBetween('requirement_date', [$request->date_start, $request->date_end]);
            }
            if ($request->filled('material')) {
                $query->where('material_number', $request->material);
            }

            $query->selectRaw('material_number as material, material_description, COUNT(*) as count')
                ->groupBy('material_number', 'material_description');

            // Sorting for grouped result
            $sortBy = $request->get('sort_by');
            $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
            if ($sortBy === 'material') {
                $query->orderBy('material', $sortDirection);
            } elseif ($sortBy === 'count') {
                $query->orderBy('count', $sortDirection);
            } else {
                $query->orderBy('count', 'desc');
            }

            $perPage = (int) $request->get('per_page', 15);
            $paginated = $query->paginate($perPage);

            $items = collect($paginated->items())->map(function ($row) {
                return [
                    'material' => $row->material,
                    'material_description' => $row->material_description,
                    'count' => (int) $row->count,
                ];
            });

            return response()->json([
                'data' => $items,
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ]);
        }

        // Default (row-level) mode
        $query = EquipmentWorkOrderMaterial::query();

        if ($request->filled('equipment_number')) {
            $query->where('equipment_number', $request->equipment_number);
        }
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('requirement_date', [$request->date_start, $request->date_end]);
        }
        if ($request->filled('material')) {
            $query->where('material_number', $request->material);
        }

        // Sorting
        $allowedSorts = [
            'requirements_date' => 'requirement_date',
            'requirement_date' => 'requirement_date',
            'order_number' => 'order_number',
            'material' => 'material_number',
            'material_number' => 'material_number',
            'requirement_quantity' => 'requirement_qty',
            'requirement_qty' => 'requirement_qty',
            'quantity_withdrawn' => 'withdrawn_qty',
            'withdrawn_qty' => 'withdrawn_qty',
            'value_withdrawn' => 'withdrawn_value',
            'withdrawn_value' => 'withdrawn_value',
        ];
        $sortBy = $request->get('sort_by');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sortBy && isset($allowedSorts[$sortBy])) {
            $query->orderBy($allowedSorts[$sortBy], $sortDirection);
        } else {
            $query->orderBy('requirement_date', 'desc');
        }

        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function show(string $orderNumber, Request $request)
    {
        if ($request->get('group_by') === 'material') {
            $items = EquipmentWorkOrderMaterial::selectRaw('material_number as material, material_description, COUNT(*) as count')
                ->where('order_number', $orderNumber)
                ->groupBy('material_number', 'material_description')
                ->orderByDesc('count')
                ->get()
                ->map(function ($row) {
                    return [
                        'material' => $row->material,
                        'material_description' => $row->material_description,
                        'count' => (int) $row->count,
                    ];
                });
            return response()->json(['data' => $items]);
        }

        $items = EquipmentWorkOrderMaterial::where('order_number', $orderNumber)
            ->orderBy('requirement_date', 'desc')
            ->get();
        if ($items->isEmpty()) {
            return response()->json(['data' => []]);
        }
        return response()->json([
            'data' => $items->map(function ($ewo) {
                return [
                    'id' => $ewo->id,
                    'order_number' => $ewo->order_number,
                    'equipment_number' => $ewo->equipment_number,
                    'requirements_date' => $ewo->requirement_date,
                    'reservation' => $ewo->reservation_number,
                    'material' => $ewo->material_number,
                    'material_description' => $ewo->material_description,
                    'requirement_quantity' => $ewo->requirement_qty,
                    'base_unit_of_measure' => $ewo->unit_of_measure,
                    'quantity_withdrawn' => $ewo->withdrawn_qty,
                    'value_withdrawn' => $ewo->withdrawn_value,
                    'currency' => $ewo->currency,
                ];
            }),
        ]);
    }
}
