<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentWorkOrderMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'order_number',
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
        'quantity_is_fixed',
        'qty_in_unit_of_entry',
        'unit_of_entry',
        'qty_for_avail_check',
        'goods_recipient',
        'material_group',
        'acct_manually',
        'commitment_item_1',
        'commitment_item_2',
        'funds_center',
        'start_time',
        'end_time',
        'service_duration',
        'service_dur_unit',
        'equipment_number',
        'material_description',
        'api_created_at',
        'api_updated_at',
    ];

    /**
     * Get the plant that owns the equipment work order material.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the work order associated with the equipment work order material.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'order_number', 'order');
    }
}
