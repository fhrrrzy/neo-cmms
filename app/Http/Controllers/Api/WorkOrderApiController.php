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

        // Filter by equipment number when provided (preferred over plant)
        if ($request->filled('equipment_number')) {
            $query->where('equipment_number', $request->equipment_number);
        } elseif ($request->filled('plant_id')) {
            // Backward-compat: allow plant filter if equipment is not specified
            $query->where('plant_id', $request->plant_id);
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('created_on', [$request->date_start, $request->date_end]);
        }

        // Optional order_type filter (supports numeric 1-4 mapping and "ANOMALY")
        if ($request->filled('order_type')) {
            $ot = (string) $request->order_type;
            $map = [
                '1' => 'PM01',
                '2' => 'PM02',
                '3' => 'PM03',
                '4' => 'PM04',
            ];
            if (strtoupper($ot) === 'ANOMALY') {
                $query->whereNotIn('order_type', array_values($map));
            } else {
                $query->where('order_type', $map[$ot] ?? $ot);
            }
        }

        // Text search across key fields (includes order number)
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('cause_text', 'like', $term)
                    ->orWhere('item_text', 'like', $term);
            });
        }

        // Sorting support
        // Map client sort keys to actual DB columns
        $allowedSorts = [
            'created_on' => 'created_on',
            'order' => 'order',
            'order_type_label' => 'order_type', // label is derived; sort by underlying column
            'order_status_label' => 'order_status', // label is derived; sort by underlying column
            'order_type' => 'order_type',
            'order_status' => 'order_status',
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
                'cause_text' => $wo->cause_text,
                'item_text' => $wo->item_text,
                'equipment_number' => $wo->equipment_number,
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
