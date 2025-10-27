<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentWorkOrder extends Model
{
    protected $fillable = [
        'uuid',
        'plant_id',
        'order_number',
        'equipment_number',
        'functional_location',
        'functional_location_description',
        'reservation',
        'requirement_type',
        'reservation_status',
        'item_deleted',
        'movement_allowed',
        'final_issue',
        'missing_part',
        'material',
        'material_description',
        'storage_location',
        'requirements_date',
        'requirement_quantity',
        'base_unit_of_measure',
        'debit_credit_ind',
        'quantity_is_fixed',
        'quantity_withdrawn',
        'value_withdrawn',
        'currency',
        'qty_in_unit_of_entry',
        'unit_of_entry',
        'movement_type',
        'gl_account',
        'receiving_plant',
        'receiving_storage_location',
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
        'api_updated_at',
    ];

    protected $casts = [
        'requirements_date' => 'date',
        'requirement_quantity' => 'decimal:2',
        'quantity_withdrawn' => 'decimal:2',
        'value_withdrawn' => 'decimal:2',
        'qty_in_unit_of_entry' => 'decimal:2',
        'qty_for_avail_check' => 'decimal:2',
        'api_updated_at' => 'datetime',
    ];

    /**
     * Get the plant that owns the equipment work order.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the work order that this material belongs to.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'order_number', 'order');
    }

    /**
     * Get the equipment that this work order material belongs to.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_number', 'equipment_number');
    }
}
