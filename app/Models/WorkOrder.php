<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'mandt',
        'order',
        'order_type',
        'created_on',
        'change_date_for_order_master',
        'description',
        'company_code',
        'plant_id',
        'station_id',
        'plant_code',
        'responsible_cctr',
        'order_status',
        'technical_completion',
        'cost_center',
        'profit_center',
        'object_class',
        'main_work_center',
        'notification',
        'cause',
        'cause_text',
        'code_group_problem',
        'item_text',
        'created',
        'released',
        'completed',
        'closed',
        'planned_release',
        'planned_completion',
        'planned_closing_date',
        'release',
        'close',
        'api_updated_at',
        'equipment_number',
        'functional_location',
        'functional_location_description',
        'opertn_task_list_no',
    ];

    protected $casts = [
        'created_on' => 'date',
        'change_date_for_order_master' => 'date',
        'technical_completion' => 'date',
        'created' => 'datetime',
        'released' => 'datetime',
        'closed' => 'datetime',
        'planned_release' => 'datetime',
        'planned_completion' => 'datetime',
        'planned_closing_date' => 'datetime',
        'release' => 'datetime',
        'close' => 'datetime',
        'api_updated_at' => 'datetime',
    ];

    /**
     * Get the plant that owns the work order.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the station associated with the work order.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Get the order status label.
     */
    public function getOrderStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            '00' => 'Created',
            '10' => 'Released',
            '20' => 'Completed',
            '30' => 'Closed',
            default => $this->order_status ?? 'Unknown',
        };
    }

    /**
     * Get the order type label.
     */
    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'PM01' => 'PM01 - Preventive Maintenance',
            'PM02' => 'PM02 - Corrective Maintenance',
            'PM03' => 'PM03 - Emergency Maintenance',
            'PM04' => 'PM04 - Project Maintenance',
            default => $this->order_type ?? 'Unknown',
        };
    }

    /**
     * Check if work order is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->completed === 'X';
    }

    /**
     * Check if work order is closed.
     */
    public function getIsClosedAttribute(): bool
    {
        return !is_null($this->closed);
    }

    /**
     * Get the equipment associated with the work order.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_number', 'equipment_number');
    }

    /**
     * Get the equipment work orders for this work order.
     */
    public function equipmentWorkOrders(): HasMany
    {
        return $this->hasMany(EquipmentWorkOrder::class, 'order_number', 'order');
    }

    /**
     * Get the equipment materials for this work order.
     */
    public function equipmentMaterials(): HasMany
    {
        return $this->hasMany(EquipmentMaterial::class, 'production_order', 'order');
    }
}
