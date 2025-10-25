<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Scopes\EquipmentFilter;
use App\Scopes\WorkOrderSearchFilter;
use App\Scopes\WorkOrderPaginator;
use Illuminate\Http\Request;

class WorkOrderApiController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::with(['plant', 'station']);

        // Apply reusable query components
        $query->tap(new EquipmentFilter($request))
            ->tap(new WorkOrderSearchFilter($request));

        // JSON where clauses for complex metadata filtering
        $query->when($request->filled('metadata'), function ($q) use ($request) {
            $metadata = $request->get('metadata');
            foreach ($metadata as $key => $value) {
                $q->whereJsonContains("metadata->{$key}", $value);
            }
        })->when($request->filled('priority_range'), function ($q) use ($request) {
            $range = $request->get('priority_range');
            $q->whereJsonLength('metadata->priority', '>=', $range['min'] ?? 1)
                ->whereJsonLength('metadata->priority', '<=', $range['max'] ?? 5);
        });

        // Use pipe for pagination with sorting
        $sortBy = $request->get('sort_by', 'created_on');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->get('per_page', 15);

        $workOrders = $query->pipe(new WorkOrderPaginator($sortBy, $sortDirection, $perPage));

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
