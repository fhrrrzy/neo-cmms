<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class WorkOrderApiController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::with(['plant', 'station']);

        if ($request->filled('plant_id')) {
            $query->where('plant_id', $request->plant_id);
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('created_on', [$request->date_start, $request->date_end]);
        }

        // Sorting support
        $allowedSorts = [
            'created_on' => 'created_on',
            'order' => 'order',
            'order_type_label' => 'order_type_label',
            'order_status_label' => 'order_status_label',
        ];
        $sortBy = $request->get('sort_by');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sortBy && isset($allowedSorts[$sortBy])) {
            $query->orderBy($allowedSorts[$sortBy], $sortDirection);
        } else {
            $query->orderBy('created_on', 'desc');
        }

        $perPage = (int) $request->get('per_page', 15);
        $workOrders = $query->paginate($perPage);

        $data = $workOrders->getCollection()->map(function ($wo) {
            return [
                'id' => $wo->id,
                'order' => $wo->order,
                'order_type' => $wo->order_type,
                'order_type_label' => $wo->order_type_label,
                'description' => $wo->description,
                'created_on' => optional($wo->created_on)->toDateString(),
                'order_status' => $wo->order_status,
                'order_status_label' => $wo->order_status_label,
                'plant' => $wo->plant ? ['id' => $wo->plant->id, 'name' => $wo->plant->name] : null,
                'station' => $wo->station ? ['id' => $wo->station->id, 'description' => $wo->station->description] : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $workOrders->total(),
            'per_page' => $workOrders->perPage(),
            'current_page' => $workOrders->currentPage(),
            'last_page' => $workOrders->lastPage(),
        ]);
    }
}
