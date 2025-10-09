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
        'station_id',
        'equipment_group_id',
        'company_code',
        'equipment_description',
        'object_number',
        'point',
        'api_created_at',
        'api_id',
        'mandt',
        'baujj',
        'groes',
        'herst',
        'mrnug',
        'eqtyp',
        'eqart',
        'maintenance_planner_group',
        'maintenance_work_center',
        'functional_location',
        'description_func_location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'api_created_at' => 'datetime',
    ];

    /**
     * Append computed attributes to model arrays
     *
     * @var array<int, string>
     */
    protected $appends = [
        'equipment_type',
    ];

    /**
     * Map eqtyp code (1-5) to human-readable equipment type.
     */
    public function getEquipmentTypeAttribute(): ?string
    {
        $map = [
            '1' => 'Mesing Produksi',
            '2' => 'Kendaraan',
            '3' => 'Alat dan Utilitas',
            '4' => 'IT & Telekomunikasi',
            '5' => 'Aset PMN',
        ];

        $code = (string) ($this->attributes['eqtyp'] ?? '');
        return $map[$code] ?? null;
    }

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
     * Get the station that the equipment belongs to.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Get the running times for the equipment.
     */
    public function runningTimes(): HasMany
    {
        return $this->hasMany(RunningTime::class, 'equipment_number', 'equipment_number');
    }

    /**
     * Get the rules associated with the equipment.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * Get the latest running time for the equipment.
     */
    public function latestRunningTime(): HasMany
    {
        return $this->hasMany(RunningTime::class, 'equipment_number', 'equipment_number')->latest('date');
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
            'api_id' => 'nullable|string|max:255',
            'mandt' => 'nullable|string|max:50',
            'baujj' => 'nullable|string|max:50',
            'groes' => 'nullable|string|max:255',
            'herst' => 'nullable|string|max:255',
            'mrnug' => 'nullable|string|max:50',
            'eqtyp' => 'nullable|string|max:50',
            'eqart' => 'nullable|string|max:100',
            'maintenance_planner_group' => 'nullable|string|max:100',
            'maintenance_work_center' => 'nullable|string|max:100',
            'functional_location' => 'nullable|string|max:255',
            'description_func_location' => 'nullable|string|max:255',
        ];
    }
}
