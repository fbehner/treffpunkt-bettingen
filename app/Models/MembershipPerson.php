<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eine Person einer Mitgliedschaft.
 */
class MembershipPerson extends Model
{
    protected $table = 'membership_people';

    public const RELATIONS = [
        'haupt' => 'Hauptperson',
        'partner' => 'Partner/in',
        'kind' => 'Kind',
        'kontakt' => 'Kontaktperson (Verein)',
    ];

    protected $fillable = [
        'membership_id', 'relation', 'first_name', 'last_name', 'birthdate', 'birth_year',
        'email', 'phone', 'sort',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'birth_year' => 'integer',
    ];

    protected static function booted(): void
    {
        // Jahrgang aus dem Geburtsdatum ableiten.
        static::saving(function (MembershipPerson $person) {
            if ($person->birthdate) {
                $person->birth_year = (int) $person->birthdate->format('Y');
            }
        });
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Alter in Jahren; nur mit Jahrgang ungefähr (Alter am Jahresende). */
    public function age(): ?int
    {
        if ($this->birthdate) {
            return $this->birthdate->age;
        }

        return $this->birth_year ? (int) now()->year - $this->birth_year : null;
    }
}
