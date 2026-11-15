<?php

namespace App\Models;

use App\Support\MembershipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    /** Wie das Gesuch eingegangen ist. */
    public const SOURCES = [
        'web' => 'Website',
        'papier' => 'Papierformular',
        'email' => 'E-Mail',
        'persoenlich' => 'Persönlich / Anlass',
    ];

    protected $fillable = [
        'type', 'source', 'received_at', 'membership_id', 'fee', 'organisation', 'first_name', 'last_name', 'birthdate',
        'partner_first_name', 'partner_last_name', 'children',
        'street', 'zip_city', 'phone', 'email',
        'guardian_name', 'guardian_email', 'guardian_confirmed_at',
        'donation', 'message', 'statutes_accepted_at', 'privacy_accepted_at',
        'status', 'notes',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'received_at' => 'date',
        'guardian_confirmed_at' => 'datetime',
        'statutes_accepted_at' => 'datetime',
        'privacy_accepted_at' => 'datetime',
        'fee' => 'integer',
        'donation' => 'integer',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /** Eingangsdatum: bei Papier/E-Mail erfasst, sonst Zeitpunkt der Online-Eingabe. */
    public function receivedDate(): ?Carbon
    {
        return $this->received_at ?? $this->created_at;
    }

    public function canBeAccepted(): bool
    {
        return $this->membership_id === null
            && in_array($this->status, ['eingegangen', 'bestaetigung'], true)
            && (! $this->needsGuardian() || $this->guardian_confirmed_at !== null || $this->source !== 'web');
    }

    /**
     * Gesuch annehmen: Mitgliedschaft mit Personen anlegen und verknüpfen.
     */
    public function accept(Carbon $joinedAt, ?int $number = null): Membership
    {
        return DB::transaction(function () use ($joinedAt, $number) {
            $type = $this->typeInfo();

            $membership = Membership::create([
                'number' => $number ?? Membership::nextNumber(),
                'type' => $type->value,
                'fee' => $this->fee,
                'organisation' => $this->organisation,
                'street' => $this->street,
                'zip_city' => $this->zip_city,
                'email' => $this->email,
                'phone' => $this->phone,
                'joined_at' => $joinedAt,
                'notes' => $this->message ? 'Mitteilung im Gesuch: '.$this->message : null,
            ]);

            $sort = 0;
            $membership->people()->create([
                'relation' => $type->isOrganisation() ? 'kontakt' : 'haupt',
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'birthdate' => $this->birthdate,
                'email' => $this->email,
                'phone' => $this->phone,
                'sort' => $sort++,
            ]);

            if ($this->partner_first_name || $this->partner_last_name) {
                $membership->people()->create([
                    'relation' => 'partner',
                    'first_name' => $this->partner_first_name ?: '–',
                    'last_name' => $this->partner_last_name,
                    'sort' => $sort++,
                ]);
            }

            foreach (self::parseChildren($this->children) as $child) {
                $membership->people()->create([
                    'relation' => 'kind',
                    'first_name' => $child['name'],
                    'last_name' => $this->last_name,
                    'birth_year' => $child['year'],
                    'sort' => $sort++,
                ]);
            }

            $this->update(['status' => 'aufgenommen', 'membership_id' => $membership->id]);

            return $membership;
        });
    }

    /** „Lena 2015“ / „Tim, Jg. 2018“ → Name und Jahrgang. */
    public static function parseChildren(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                $year = preg_match('/\b(19|20)\d{2}\b/', $line, $m) ? (int) $m[0] : null;
                $name = trim(preg_replace(['/\b(19|20)\d{2}\b/', '/\b(Jg\.?|Jahrgang)\s*/i', '/[,;:()\-–]+/'], ' ', $line));

                return ['name' => preg_replace('/\s+/', ' ', $name) ?: $line, 'year' => $year];
            })
            ->values()
            ->all();
    }

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
