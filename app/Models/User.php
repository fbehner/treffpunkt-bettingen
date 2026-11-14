<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Rollen in der Verwaltung. */
    public const ROLES = [
        'admin' => 'Administration (alles, inkl. Benutzer)',
        'redaktion' => 'Redaktion (Inhalte bearbeiten)',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /** Standardwerte – gleich wie in der Migration. */
    protected $attributes = [
        'role' => 'redaktion',
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Nur aktive Konten dürfen in die Verwaltung. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && array_key_exists($this->role, self::ROLES);
    }
}
