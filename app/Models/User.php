<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'producer', 'transformer', 'distributor', 'consumer'];

    public const BACK_OFFICE_ROLES = ['admin', 'producer', 'transformer', 'distributor'];

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function producer(): HasOne
    {
        return $this->hasOne(Producer::class);
    }

    public function transformer(): HasOne
    {
        return $this->hasOne(Transformer::class);
    }

    public function distributor(): HasOne
    {
        return $this->hasOne(Distributor::class);
    }

    public function consumer(): HasOne
    {
        return $this->hasOne(Consumer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function hasRole(string|array $role): bool
    {
        $roles = (array) $role;

        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function canAccessBackOffice(): bool
    {
        return $this->hasRole(self::BACK_OFFICE_ROLES);
    }

    /**
     * Where the user lands after login, depending on the role.
     */
    public function homeRoute(): string
    {
        return $this->canAccessBackOffice() ? 'admin.dashboard' : 'consumer.profile';
    }
}
