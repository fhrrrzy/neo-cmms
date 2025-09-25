<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plant_code',
        'regional_id',
        'name',
        'kaps_terpasang',
        'faktor_koreksi_kaps',
        'faktor_koreksi_utilitas',
        'unit',
        'instalasi_bunch_press',
        'pln_isasi',
        'cofiring',
        'hidden_pica_cost',
        'hidden_pica_cpo',
        'jenis',
        'kaps_terpasang_sf',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'instalasi_bunch_press' => 'boolean',
        'pln_isasi' => 'boolean',
        'cofiring' => 'boolean',
        'hidden_pica_cost' => 'boolean',
        'hidden_pica_cpo' => 'boolean',
    ];

    /**
     * Get the equipment for the plant.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * Get the equipment running times for the plant.
     */
    public function equipmentRunningTimes(): HasMany
    {
        return $this->hasMany(EquipmentRunningTime::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'regional_id');
    }

    /**
     * Scope a query to only include active plants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get validation rules for the plant.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'plant_code' => 'required|string|max:50|unique:plants,plant_code',
            'regional_id' => 'required|exists:regions,id',
            'name' => 'required|string|max:255',
            'kaps_terpasang' => 'required|integer',
            'faktor_koreksi_kaps' => 'required|integer',
            'faktor_koreksi_utilitas' => 'required|integer',
            'unit' => 'required|integer',
            'instalasi_bunch_press' => 'required|boolean',
            'pln_isasi' => 'required|boolean',
            'cofiring' => 'required|boolean',
            'hidden_pica_cost' => 'required|boolean',
            'hidden_pica_cpo' => 'required|boolean',
            'jenis' => 'required|integer',
            'kaps_terpasang_sf' => 'required|integer',
            'is_active' => 'boolean',
        ];
    }
}
