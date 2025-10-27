<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaterial extends Model
{
    protected $fillable = [
        'uuid',
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

    protected $casts = [
        'requirement_date' => 'date',
        'requirement_qty' => 'decimal:2',
        'issued_qty' => 'decimal:2',
        'withdrawn_qty' => 'decimal:2',
        'withdrawn_value' => 'decimal:2',
        'entry_qty' => 'decimal:2',
        'api_created_at' => 'datetime',
    ];

    /**
     * Get the plant that owns the equipment material.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the work order (via production_order) that this material belongs to.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'production_order', 'order');
    }
}
