<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Reservierbare Raum-Pakete. Jedes Paket belegt einen oder mehrere Räume;
 * zwei Reservationen stören sich, wenn ihre Pakete einen Raum teilen.
 */
enum RoomPackage: string
{
    case Cafe = 'cafe';
    case Gruppenraum = 'gruppenraum';
    case Kinderraum = 'kinderraum';
    case Untergeschoss = 'untergeschoss';
    case Haus = 'haus';

    /** Die einzelnen Räume des Hauses. */
    public const ROOMS = [
        'cafe' => 'Café',
        'gruppenraum' => 'Gruppen- und Jugendraum',
        'kinderraum' => 'Familien- und Kinderraum',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Cafe => 'Café',
            self::Gruppenraum => 'Gruppen- und Jugendraum',
            self::Kinderraum => 'Familien- und Kinderraum',
            self::Untergeschoss => 'Ganzes Untergeschoss',
            self::Haus => 'Ganzes Haus',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Cafe => 'Erdgeschoss, rund 42 Plätze – nach Betriebsschluss',
            self::Gruppenraum => 'Untergeschoss: Kurs- und Gruppentisch, Lounge, Töggelikasten',
            self::Kinderraum => 'Untergeschoss: Spielturm, Krabbel- und Polsterzone',
            self::Untergeschoss => 'Beide Räume im Untergeschoss',
            self::Haus => 'Café und beide Räume im Untergeschoss',
        };
    }

    /** @return list<string> Schlüssel aus ROOMS */
    public function rooms(): array
    {
        return match ($this) {
            self::Cafe => ['cafe'],
            self::Gruppenraum => ['gruppenraum'],
            self::Kinderraum => ['kinderraum'],
            self::Untergeschoss => ['gruppenraum', 'kinderraum'],
            self::Haus => ['cafe', 'gruppenraum', 'kinderraum'],
        };
    }

    public function includesCafe(): bool
    {
        return in_array('cafe', $this->rooms(), true);
    }

    /** Pakete, die mindestens einen Raum mit diesem teilen (inkl. sich selbst). */
    public function conflictingValues(): array
    {
        return collect(self::cases())
            ->filter(fn (self $other) => array_intersect($this->rooms(), $other->rooms()) !== [])
            ->map(fn (self $other) => $other->value)
            ->values()
            ->all();
    }

    /** Stundentarif in CHF aus den Einstellungen. */
    public function hourlyRate(bool $member): int
    {
        return (int) Setting::get('tariff_'.$this->value.($member ? '_mitglied' : '_gast'), 0);
    }

    /** Preis für eine Dauer in Minuten, auf ganze Franken gerundet. */
    public function price(int $minutes, bool $member): int
    {
        return (int) round($this->hourlyRate($member) * $minutes / 60);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $p) => [$p->value => $p->label()])->all();
    }
}
