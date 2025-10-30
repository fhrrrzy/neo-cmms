<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'no',
        'name',
        'category',
    ];

    /**
     * Boot the model and auto-generate UUID.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'regional_id');
    }

    /**
     * Get the users that belong to the region.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'regional_id');
    }
}
