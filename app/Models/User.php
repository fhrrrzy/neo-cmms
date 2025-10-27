<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
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
     * Check if the user can access the specified Filament panel.
     * Only superadmin users can access the admin panel.
     *
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only superadmin can access the admin panel
        if ($panel->getId() === 'admin') {
            return $this->isSuperadmin();
        }

        return false;
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
