<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'ims_id',
        'plant_id',
        'material_number',
        'reservation_number',
        'reservation_item',
        'reservation_type',
        'requirement_type',
        'reservation_status',
        'deletion_flag',
        'goods_receipt_flag',
        'final_issue_flag',
        'error_flag',
        'storage_location',
        'production_supply_area',
        'batch_number',
        'storage_bin',
        'special_stock_indicator',
        'requirement_date',
        'requirement_qty',
        'unit_of_measure',
        'debit_credit_indicator',
        'issued_qty',
        'withdrawn_qty',
        'withdrawn_value',
        'currency',
        'entry_qty',
        'entry_uom',
        'planned_order',
        'purchase_requisition',
        'purchase_requisition_item',
        'production_order',
        'movement_type',
        'gl_account',
        'receiving_storage_loc',
        'receiving_plant',
        'api_created_at',
    ];

    /**
     * Get the plant that owns the equipment material.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }


    /**
     * Link to the work order via production_order → work_orders.order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'production_order', 'order');
    }


    /**
     * Get the equipment work orders associated with the equipment material (by material number).
     * Note: This returns a collection since multiple work orders can have the same material.
     */
    public function equipmentWorkOrdersByMaterial()
    {
        return $this->hasMany(EquipmentWorkOrder::class, 'material', 'material_number');
    }

    /**
     * Get the equipment work orders associated with the equipment material (by reservation number).
     * Note: This returns a collection since multiple work orders can have the same reservation.
     */
    public function equipmentWorkOrdersByReservation()
    {
        return $this->hasMany(EquipmentWorkOrder::class, 'reservation', 'reservation_number');
    }
}
