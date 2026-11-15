<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Mitgliedschaftsarten laut Beitrittsgesuch und Statuten (Art. 11).
 * Die Beträge sind in der Verwaltung unter „Mitgliedschaft“ änderbar.
 */
enum MembershipType: string
{
    case Einzel = 'einzel';
    case Paar = 'paar';
    case Familie = 'familie';
    case Jugend = 'jugend';
    case Kind = 'kind';
    case Verein = 'verein';

    public function label(): string
    {
        return match ($this) {
            self::Einzel => 'Einzelperson',
            self::Paar => 'Paar',
            self::Familie => 'Familie',
            self::Jugend => 'Jugendliche/r',
            self::Kind => 'Kind',
            self::Verein => 'Verein / Institution',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Einzel => 'ab 18 Jahren',
            self::Paar => 'zwei Personen, ein Haushalt',
            self::Familie => 'Eltern mit Kindern',
            self::Jugend => '16 bis 17 Jahre',
            self::Kind => '12 bis 15 Jahre',
            self::Verein => 'mit Kontaktperson',
        };
    }

    /** Jahresbeitrag in CHF (aus den Einstellungen). */
    public function fee(): int
    {
        return (int) Setting::get('fee_'.$this->value, Setting::DEFAULTS['fee_'.$this->value]);
    }

    /** Erlaubtes Alter [von, bis] – nur für Kinder und Jugendliche. */
    public function ageRange(): ?array
    {
        return match ($this) {
            self::Kind => [12, 15],
            self::Jugend => [16, 17],
            default => null,
        };
    }

    public function needsGuardian(): bool
    {
        return $this->ageRange() !== null;
    }

    public function hasPartner(): bool
    {
        return in_array($this, [self::Paar, self::Familie], true);
    }

    public function hasChildren(): bool
    {
        return $this === self::Familie;
    }

    public function isOrganisation(): bool
    {
        return $this === self::Verein;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
