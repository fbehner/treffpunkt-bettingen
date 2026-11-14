<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Einfache Schlüssel/Wert-Einstellungen (Texte der Startseite, Kontakt …).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];

    private const CACHE_KEY = 'settings.all';

    /** Standardtexte – entsprechen dem Plakat. */
    public const DEFAULTS = [
        'site_name' => 'Treffpunkt Bettingen',
        'kicker' => 'Ein Café in Planung',
        'headline_dark' => 'Ein Ort des',
        'headline_accent' => "aktiven\nTreffens.",
        'intro' => 'Die Räumlichkeiten sind ein Treffpunkt für alle. Das alles könnte hier stattfinden:',
        'closing_dark' => 'Auch für eure',
        'closing_accent' => 'Ideen.',
        'closing_text' => 'Ihr habt eine Idee für eine Veranstaltung, einen Kurs oder ein Treffen? Wir freuen uns, davon zu hören.',
        'contact_email' => '',
        'address' => '',
        'footer_text' => 'Ein Verein für Begegnung in Bettingen.',
    ];

    public static function values(): array
    {
        try {
            $stored = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => static::query()->pluck('value', 'key')->all()
            );
        } catch (\Throwable) {
            // z. B. Datenbank noch nicht eingerichtet – Standardtexte verwenden.
            $stored = [];
        }

        return array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::values()[$key] ?? $default;
    }

    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
