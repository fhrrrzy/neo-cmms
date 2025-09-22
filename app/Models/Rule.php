<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'equipment_group_id',
        'equipment_id',
        'rules',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the equipment group that owns the rule.
     */
    public function equipmentGroup(): BelongsTo
    {
        return $this->belongsTo(EquipmentGroup::class);
    }

    /**
     * Get the equipment that owns the rule.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Scope a query to only include active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by equipment group.
     */
    public function scopeByEquipmentGroup($query, $equipmentGroupId)
    {
        return $query->where('equipment_group_id', $equipmentGroupId);
    }

    /**
     * Scope a query to filter by equipment.
     */
    public function scopeByEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * Get the target name (equipment group name or equipment number).
     */
    public function getTargetNameAttribute(): string
    {
        if ($this->equipment_group_id) {
            return $this->equipmentGroup?->name ?? 'Unknown Group';
        }
        
        if ($this->equipment_id) {
            return $this->equipment?->equipment_number ?? 'Unknown Equipment';
        }
        
        return 'No Target';
    }

    /**
     * Get validation rules for the rule.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'equipment_group_id' => 'nullable|exists:equipment_groups,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'rules' => 'required|array',
            'rules.*.number' => 'required|numeric|min:0',
            'rules.*.action' => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
