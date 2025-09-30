<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'plant_id',
        'regional_id',
        'subholding_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the plant that the user belongs to.
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the region that the user belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'regional_id');
    }

    /**
     * Get the subholding that the user belongs to.
     */
    public function subholding(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'subholding_id');
    }

    /**
     * Check if user is superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is PKS.
     */
    public function isPks(): bool
    {
        return $this->role === 'pks';
    }

    /**
     * Scope a query to only include superadmin users.
     */
    public function scopeSuperadmin($query)
    {
        return $query->where('role', 'superadmin');
    }

    /**
     * Scope a query to only include PKS users.
     */
    public function scopePks($query)
    {
        return $query->where('role', 'pks');
    }

    /**
     * Scope a query to filter by plant.
     */
    public function scopeByPlant($query, $plantId)
    {
        return $query->where('plant_id', $plantId);
    }

    /**
     * Get validation rules for the user.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:superadmin,pks',
            'plant_id' => 'nullable|exists:plants,id',
        ];
    }
}
