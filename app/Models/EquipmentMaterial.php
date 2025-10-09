<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'ims_id',
        'plant_id',
        'equipment_number',
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
     * Get the equipment associated with the equipment material.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_number', 'equipment_number');
    }

    /**
     * Get the equipment work order associated with the equipment material (by reservation).
     */
    public function equipmentWorkOrderByReservation(): BelongsTo
    {
        return $this->belongsTo(EquipmentWorkOrder::class, 'reservation_number', 'reservation');
    }

    /**
     * Get the equipment work order associated with the equipment material (by material number).
     */
    public function equipmentWorkOrderByMaterial(): BelongsTo
    {
        return $this->belongsTo(EquipmentWorkOrder::class, 'material_number', 'material');
    }
}
