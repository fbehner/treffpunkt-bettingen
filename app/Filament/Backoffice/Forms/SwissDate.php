<?php

namespace App\Filament\Backoffice\Forms;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;

/**
 * Datumsfeld zum Eintippen im Schweizer Format TT.MM.JJJJ – unabhängig von
 * Browser- und Systemsprache. Gespeichert wird als Y-m-d.
 */
class SwissDate
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->placeholder('TT.MM.JJJJ')
            ->mask(RawJs::make("'99.99.9999'"))
            ->inputMode('numeric')
            ->maxLength(10)
            ->formatStateUsing(fn ($state) => self::toDisplay($state))
            ->dehydrateStateUsing(fn ($state) => self::toStorage($state))
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                if (blank($value)) {
                    return;
                }
                if (self::parse($value) === null) {
                    $fail('Bitte das Datum als TT.MM.JJJJ eingeben, z. B. 15.03.1980.');
                }
            });
    }

    /** Geburtsdatum: nicht in der Zukunft. */
    public static function birthdate(string $name = 'birthdate'): TextInput
    {
        return self::make($name)
            ->label('Geburtsdatum')
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                $date = self::parse($value);
                if ($date && $date->isFuture()) {
                    $fail('Das Geburtsdatum darf nicht in der Zukunft liegen.');
                }
            });
    }

    public static function parse(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        $value = trim((string) $value);

        // Eingabe TT.MM.JJJJ (auch mit einstelligem Tag/Monat), sonst Datenbankformat.
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay()
                : null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            try {
                // ISO-Zeitstempel (z. B. „…T23:00:00Z“) zuerst in die Ortszeit umrechnen.
                if (preg_match('/T|Z|[+-]\d{2}:\d{2}$/', substr($value, 10))) {
                    return Carbon::parse($value)->setTimezone(config('app.timezone'))->startOfDay();
                }

                return Carbon::parse(substr($value, 0, 10))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    public static function toDisplay(mixed $state): ?string
    {
        return self::parse($state)?->format('d.m.Y') ?? (filled($state) ? (string) $state : null);
    }

    public static function toStorage(mixed $state): ?string
    {
        return self::parse($state)?->toDateString();
    }
}
