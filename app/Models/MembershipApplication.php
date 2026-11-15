<?php

namespace App\Models;

use App\Support\MembershipType;
use Illuminate\Database\Eloquent\Model;

/**
 * Ein Beitrittsgesuch, online eingereicht.
 */
class MembershipApplication extends Model
{
    public const STATUSES = [
        'bestaetigung' => 'Wartet auf Erziehungsberechtigte',
        'eingegangen' => 'Eingegangen',
        'aufgenommen' => 'Aufgenommen',
        'abgelehnt' => 'Abgelehnt',
        'zurueckgezogen' => 'Zurückgezogen',
    ];

    protected $fillable = [
        'type', 'fee', 'organisation', 'first_name', 'last_name', 'birthdate',
        'partner_first_name', 'partner_last_name', 'children',
        'street', 'zip_city', 'phone', 'email',
        'guardian_name', 'guardian_email', 'guardian_confirmed_at',
        'donation', 'message', 'statutes_accepted_at', 'privacy_accepted_at',
        'status', 'notes',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'guardian_confirmed_at' => 'datetime',
        'statutes_accepted_at' => 'datetime',
        'privacy_accepted_at' => 'datetime',
        'fee' => 'integer',
        'donation' => 'integer',
    ];

    public function typeInfo(): MembershipType
    {
        return MembershipType::from($this->type);
    }

    public function displayName(): string
    {
        $name = trim($this->first_name.' '.$this->last_name);

        if ($this->organisation) {
            return $this->organisation.' ('.$name.')';
        }

        if ($this->partner_first_name || $this->partner_last_name) {
            $name .= ' und '.trim($this->partner_first_name.' '.$this->partner_last_name);
        }

        return $name;
    }

    public function needsGuardian(): bool
    {
        return $this->typeInfo()->needsGuardian();
    }
}
