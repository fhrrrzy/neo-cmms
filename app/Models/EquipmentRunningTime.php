<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentRunningTime extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'equipment_id',
        'plant_id',
        'point',
        'date',
        'date_time',
        'description',
        'running_hours',
        'cumulative_hours',
        'company_code',
        'equipment_description',
        'object_number',
        'api_created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'date_time' => 'datetime',
        'running_hours' => 'decimal:2',
        'cumulative_hours' => 'decimal:2',
        'api_created_at' => 'datetime',
    ];

    /**
     * Get the equipment that owns the running time.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the plant that owns the running time.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Scope a query to filter by equipment.
     */
    public function scopeByEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * Scope a query to filter by plant.
     */
    public function scopeByPlant($query, $plantId)
    {
        return $query->where('plant_id', $plantId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get records from the last N days.
     */
    public function scopeLastDays($query, $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    /**
     * Get validation rules for the equipment running time.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'equipment_id' => 'required|exists:equipment,id',
            'plant_id' => 'required|exists:plants,id',
            'point' => 'nullable|string|max:50',
            'date' => 'required|date',
            'date_time' => 'nullable|date',
            'description' => 'nullable|string',
            'running_hours' => 'required|numeric|min:0',
            'cumulative_hours' => 'required|numeric|min:0',
            'company_code' => 'nullable|string|max:50',
            'equipment_description' => 'nullable|string|max:255',
            'object_number' => 'nullable|string|max:50',
            'api_created_at' => 'nullable|date',
        ];
    }
}
