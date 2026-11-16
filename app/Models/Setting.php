<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Einfache Schlüssel/Wert-Einstellungen (Kontakt, Hinweisband, Beiträge …).
 * Die Seiteninhalte selbst stehen in den Seiten (Page) als Bausteine.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];

    private const CACHE_KEY = 'settings.all';

    public const DEFAULTS = [
        'site_name' => 'Treffpunkt Bettingen',
        'meta_description' => 'Dorfladen & Café an der Hauptstrasse 85: ein Treffpunkt für ganz Bettingen. Verein Treffpunkt Bettingen.',
        'contact_email' => 'info@treffpunkt-bettingen.ch',
        'address' => "Verein Treffpunkt Bettingen\n4126 Bettingen",
        'footer_text' => 'Von Bettingern für Bettinger.',
        'statutes_file' => 'dokumente/statuten-treffpunkt-bettingen.pdf',

        // Hinweisband ganz oben (leer = ausgeblendet)
        'banner_label' => 'Nächster Schritt',
        'banner_text' => 'Einwohnergemeindeversammlung am 8. Dezember 2026 – Grundsatzentscheid zum Konzept',
        'banner_link' => '/projekt#stand',

        // Jahresbeiträge in CHF (Beitrittsgesuch)
        'fee_einzel' => 50,
        'fee_paar' => 80,
        'fee_familie' => 80,
        'fee_jugend' => 20,
        'fee_kind' => 10,
        'fee_verein' => 100,
        'membership_note' => 'Der Mitgliederbeitrag wird jeweils per 1. Januar fällig, erstmals im Januar 2027 – falls das Projekt an der Gemeindeversammlung vom 8. Dezember 2026 bewilligt wird. Andernfalls fällt das Gesuch ohne Kostenfolge dahin.',

        // Raumreservation
        'reservation_email' => '', // leer = Kontakt-E-Mail
        'reservation_from' => '07:00',
        'reservation_until' => '23:00',
        'cafe_from' => '18:00', // Café erst nach Betriebsschluss
        'reservation_lead_days' => 3,
        'reservation_max_months' => 12,
        'house_rules_file' => '',
        'reservation_note' => 'Die Reservation wird erst mit unserer Bestätigung per E-Mail verbindlich. Mitglieder haben Vorrang. Die Rechnung folgt nach dem Anlass.',

        // Stundentarife in CHF je Paket (0 = Tarif auf Anfrage)
        'tariff_cafe_mitglied' => 0,
        'tariff_cafe_gast' => 0,
        'tariff_gruppenraum_mitglied' => 0,
        'tariff_gruppenraum_gast' => 0,
        'tariff_kinderraum_mitglied' => 0,
        'tariff_kinderraum_gast' => 0,
        'tariff_untergeschoss_mitglied' => 0,
        'tariff_untergeschoss_gast' => 0,
        'tariff_haus_mitglied' => 0,
        'tariff_haus_gast' => 0,
    ];

    /** Empfänger der Meldungen zu neuen Reservationsanfragen. */
    public static function reservationEmail(): ?string
    {
        return filled(self::get('reservation_email')) ? self::get('reservation_email') : self::get('contact_email');
    }

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
