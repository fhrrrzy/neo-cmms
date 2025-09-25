<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunningTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'equipment_number',
        'date',
        'plant_id',
        'date_time',
        'running_hours',
        'counter_reading',
        'maintenance_text',
        'company_code',
        'equipment_description',
        'object_number',
        'api_created_at',
    ];

    protected $casts = [
        'date' => 'date',
        'date_time' => 'datetime',
        'running_hours' => 'decimal:2',
        'counter_reading' => 'decimal:2',
        'api_created_at' => 'datetime',
    ];

    /**
     * Get the plant that owns the running time.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
