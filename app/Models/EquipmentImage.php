<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentImage extends Model
{
    protected $table = 'equipment_images';

    protected $fillable = [
        'equipment_number',
        'name',
        'filepath',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_number', 'equipment_number');
    }
}

