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

    /**
     * Rollen. Ein Konto kann mehrere haben.
     * - admin: Website-Administration (Benutzer, Einstellungen, alle Inhalte)
     * - redaktion: Inhalte der Website bearbeiten
     * - mitglieder: Backoffice – Gesuche, Mitglieder, Beiträge (Personendaten)
     * - reservationen: Backoffice – Raumreservationen (ohne Mitgliederdaten)
     */
    public const ROLES = [
        'admin' => 'Webadmin',
        'redaktion' => 'Redaktion',
        'mitglieder' => 'Mitgliederverwaltung',
        'reservationen' => 'Reservationen',
    ];

    public const ROLE_HINTS = [
        'admin' => 'Website: Benutzerkonten, Einstellungen und alle Inhalte',
        'redaktion' => 'Website: Seiten, Angebote und Aktuelles bearbeiten',
        'mitglieder' => 'Backoffice: Beitrittsgesuche, Mitglieder und Beiträge',
        'reservationen' => 'Backoffice: Raumreservationen bestätigen und erfassen',
    ];

    /** Welche Rollen in welchen Bereich dürfen. */
    public const PANEL_ROLES = [
        'admin' => ['admin', 'redaktion'],
        'backoffice' => ['mitglieder', 'reservationen'],
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
        'is_active',
    ];

    /** Standardwerte – gleich wie in der Migration. */
    protected $attributes = [
        'roles' => '["redaktion"]',
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
            'roles' => 'array',
        ];
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function canManageMembers(): bool
    {
        return $this->is_active && $this->hasRole('mitglieder');
    }

    public function canManageReservations(): bool
    {
        return $this->is_active && $this->hasRole('reservationen');
    }

    /** Nur aktive Konten mit passender Rolle dürfen in einen Bereich. */
    public function canAccessPanel(Panel $panel): bool
    {
        $allowed = self::PANEL_ROLES[$panel->getId()] ?? [];

        return $this->is_active && count(array_intersect($allowed, $this->roles ?? [])) > 0;
    }

    /** Rollen als lesbare Liste. */
    public function roleLabels(): string
    {
        return collect($this->roles ?? [])->map(fn ($r) => self::ROLES[$r] ?? $r)->join(', ');
    }
}
