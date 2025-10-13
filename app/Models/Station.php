<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plant_id',
        'cost_center',
        'description',
        'keterangan',
    ];

    /**
     * Get the plant that owns the station.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the equipment linked to this station.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * Get the work orders linked to this station.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Get the plant code from the cost_center field.
     * Cost center format: {PLANT_CODE}{STATION_CODE} (e.g., 1F12STAS01)
     */
    public function getPlantCodeAttribute(): string
    {
        // Extract plant code from cost_center by removing the station code suffix
        $costCenter = $this->cost_center;
        $stationCodes = ['STAS01', 'STAS02', 'STAS03', 'STAS04', 'STAS05', 'STAS06', 'STAS07', 'STAS08', 'STAS09', 'STAS10', 'STAS11', 'STAS12', 'STAS13', 'STAS14', 'STAS19'];

        foreach ($stationCodes as $stationCode) {
            if (str_ends_with($costCenter, $stationCode)) {
                return substr($costCenter, 0, -strlen($stationCode));
            }
        }

        // Fallback: try to extract plant code by removing common patterns
        return preg_replace('/STAS\d{2}$/', '', $costCenter);
    }

    /**
     * Get the station code from the cost_center field.
     */
    public function getStationCodeAttribute(): string
    {
        $costCenter = $this->cost_center;
        $stationCodes = ['STAS01', 'STAS02', 'STAS03', 'STAS04', 'STAS05', 'STAS06', 'STAS07', 'STAS08', 'STAS09', 'STAS10', 'STAS11', 'STAS12', 'STAS13', 'STAS14', 'STAS19'];

        foreach ($stationCodes as $stationCode) {
            if (str_ends_with($costCenter, $stationCode)) {
                return $stationCode;
            }
        }

        return '';
    }

    /**
     * Scope to filter stations by plant codes.
     */
    public function scopeByPlantCodes($query, array $plantCodes)
    {
        if (empty($plantCodes)) {
            return $query;
        }

        $conditions = [];
        foreach ($plantCodes as $plantCode) {
            $conditions[] = "cost_center LIKE '{$plantCode}%'";
        }

        return $query->whereRaw('(' . implode(' OR ', $conditions) . ')');
    }

    /**
     * Scope to filter stations by station codes.
     */
    public function scopeByStationCodes($query, array $stationCodes)
    {
        if (empty($stationCodes)) {
            return $query;
        }

        $conditions = [];
        foreach ($stationCodes as $stationCode) {
            $conditions[] = "cost_center LIKE '%{$stationCode}'";
        }

        return $query->whereRaw('(' . implode(' OR ', $conditions) . ')');
    }
}
