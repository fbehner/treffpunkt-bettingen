<?php

namespace App\Models;

use App\Support\RoomPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eine Raumreservation. Über die Website entsteht eine Anfrage; erst die
 * Bestätigung im Backoffice belegt den Raum. Einträge aus dem Backoffice
 * (z. B. Jugendtreff) können direkt bestätigt erfasst werden.
 */
class RoomReservation extends Model
{
    public const STATUSES = [
        'angefragt' => 'Angefragt',
        'bestaetigt' => 'Bestätigt',
        'abgelehnt' => 'Abgelehnt',
        'storniert' => 'Storniert',
    ];

    public const SOURCES = [
        'web' => 'Website',
        'intern' => 'Backoffice',
        'email' => 'E-Mail',
        'telefon' => 'Telefon',
        'persoenlich' => 'Persönlich',
    ];

    /** Status, die zählen, wenn es um Überschneidungen geht. */
    public const OPEN = ['angefragt', 'bestaetigt'];

    protected $fillable = [
        'package', 'starts_at', 'ends_at', 'status', 'source',
        'purpose', 'public_title', 'participants',
        'organisation', 'first_name', 'last_name', 'street', 'zip_city', 'email', 'phone',
        'is_member', 'member_number', 'price',
        'message', 'notes', 'decision_note', 'decided_at',
        'house_rules_accepted_at', 'privacy_accepted_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'decided_at' => 'datetime',
        'house_rules_accepted_at' => 'datetime',
        'privacy_accepted_at' => 'datetime',
        'is_member' => 'boolean',
        'participants' => 'integer',
        'price' => 'integer',
    ];

    public function packageInfo(): RoomPackage
    {
        return RoomPackage::from($this->package);
    }

    /** Reservationen, die einen der Räume des Pakets im Zeitraum belegen. */
    public function scopeOverlapping(Builder $query, RoomPackage $package, Carbon $start, Carbon $end): Builder
    {
        return $query->whereIn('package', $package->conflictingValues())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'bestaetigt');
    }

    /** Bestätigte Reservationen, die mit dieser kollidieren. */
    public function confirmedConflicts(): Collection
    {
        return self::query()->confirmed()
            ->overlapping($this->packageInfo(), $this->starts_at, $this->ends_at)
            ->whereKeyNot($this->getKey())
            ->orderBy('starts_at')
            ->get();
    }

    /** Andere offene Anfragen für dieselbe Zeit und dieselben Räume. */
    public function competingRequests(): Collection
    {
        return self::query()->where('status', 'angefragt')
            ->overlapping($this->packageInfo(), $this->starts_at, $this->ends_at)
            ->whereKeyNot($this->getKey())
            ->orderBy('created_at')
            ->get();
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === 'angefragt' && $this->confirmedConflicts()->isEmpty();
    }

    public function durationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /** Preis nach aktuellem Tarif (für Einträge ohne gespeicherten Preis). */
    public function calculatedPrice(): int
    {
        return $this->packageInfo()->price($this->durationMinutes(), $this->is_member);
    }

    /** z. B. „Fr, 12.12.2026, 18.00–21.30 Uhr“ */
    public function timeLabel(): string
    {
        return self::weekday($this->starts_at).', '.$this->starts_at->format('d.m.Y').', '
            .$this->starts_at->format('H.i').'–'.$this->ends_at->format('H.i').' Uhr';
    }

    public static function weekday(Carbon $date): string
    {
        return ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][$date->dayOfWeek];
    }

    public function contactName(): string
    {
        $name = trim($this->first_name.' '.$this->last_name);

        if ($this->organisation) {
            return $name !== '' ? $this->organisation.' ('.$name.')' : $this->organisation;
        }

        return $name !== '' ? $name : '–';
    }

    /** Titel im öffentlichen Kalender – ohne Personendaten. */
    public function publicLabel(): string
    {
        return filled($this->public_title) ? $this->public_title : 'Belegt';
    }

    /** Aktive Mitgliedschaft mit derselben E-Mail (Hinweis zum Tarif). */
    public function matchingMembership(): ?Membership
    {
        if (blank($this->email)) {
            return null;
        }

        $email = mb_strtolower($this->email);

        return Membership::query()->active()
            ->where(fn (Builder $q) => $q->whereRaw('LOWER(email) = ?', [$email])
                ->orWhereHas('people', fn (Builder $p) => $p->whereRaw('LOWER(email) = ?', [$email])))
            ->first();
    }
}
