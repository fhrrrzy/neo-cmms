<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'equipment_number',
        'plant_id',
        'equipment_group_id',
        'company_code',
        'equipment_description',
        'object_number',
        'point',
        'api_created_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'api_created_at' => 'datetime',
    ];

    /**
     * Get the plant that owns the equipment.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the equipment group that owns the equipment.
     */
    public function equipmentGroup(): BelongsTo
    {
        return $this->belongsTo(EquipmentGroup::class);
    }

    /**
     * Get the running times for the equipment.
     */
    public function runningTimes(): HasMany
    {
        return $this->hasMany(EquipmentRunningTime::class);
    }

    /**
     * Get the latest running time for the equipment.
     */
    public function latestRunningTime(): HasMany
    {
        return $this->hasMany(EquipmentRunningTime::class)->latest('date');
    }

    /**
     * Scope a query to only include active equipment.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by plant.
     */
    public function scopeByPlant($query, $plantId)
    {
        return $query->where('plant_id', $plantId);
    }

    /**
     * Scope a query to filter by equipment group.
     */
    public function scopeByEquipmentGroup($query, $equipmentGroupId)
    {
        return $query->where('equipment_group_id', $equipmentGroupId);
    }

    /**
     * Get validation rules for the equipment.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'equipment_number' => 'required|string|max:50|unique:equipment,equipment_number',
            'plant_id' => 'required|exists:plants,id',
            'equipment_group_id' => 'required|exists:equipment_groups,id',
            'company_code' => 'nullable|string|max:50',
            'equipment_description' => 'nullable|string|max:255',
            'object_number' => 'nullable|string|max:50',
            'point' => 'nullable|string|max:50',
            'api_created_at' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }
}
