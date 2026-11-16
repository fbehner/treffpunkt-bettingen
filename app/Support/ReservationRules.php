<?php

namespace App\Support;

use App\Models\RoomReservation;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Regeln für Anfragen über die Website (Zeiten aus den Einstellungen).
 * Das Backoffice ist daran nicht gebunden.
 */
class ReservationRules
{
    /** Uhrzeiten im Halbstundentakt zwischen frühestem Beginn und spätestem Ende. */
    public static function timeOptions(): array
    {
        $from = self::minutes(Setting::get('reservation_from')) ?? 7 * 60;
        $until = self::minutes(Setting::get('reservation_until')) ?? 23 * 60;

        $times = [];
        for ($m = $from; $m <= $until; $m += 30) {
            $times[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $times;
    }

    public static function earliestDate(): Carbon
    {
        return now()->startOfDay()->addDays((int) Setting::get('reservation_lead_days', 3));
    }

    public static function latestDate(): Carbon
    {
        return now()->startOfDay()->addMonths((int) Setting::get('reservation_max_months', 12));
    }

    public static function cafeFrom(): ?string
    {
        $value = Setting::get('cafe_from');

        return self::minutes($value) !== null ? $value : null;
    }

    /**
     * Prüft eine gewünschte Zeit. Gibt eine Fehlermeldung zurück oder null.
     */
    public static function check(RoomPackage $package, Carbon $start, Carbon $end): ?string
    {
        if ($end->lessThanOrEqualTo($start)) {
            return 'Das Ende muss nach dem Beginn liegen.';
        }

        if ($start->lt(self::earliestDate())) {
            return 'Reservationen sind frühestens ab dem '.self::earliestDate()->format('d.m.Y').' möglich. Für kurzfristige Anfragen schreibt uns bitte eine E-Mail.';
        }

        if ($start->gt(self::latestDate())) {
            return 'Reservationen sind höchstens bis zum '.self::latestDate()->format('d.m.Y').' möglich.';
        }

        $startMin = $start->hour * 60 + $start->minute;
        $endMin = $end->isSameDay($start) ? $end->hour * 60 + $end->minute : 24 * 60;
        $from = self::minutes(Setting::get('reservation_from')) ?? 0;
        $until = self::minutes(Setting::get('reservation_until')) ?? 24 * 60;

        if (! $end->isSameDay($start) && ! ($end->isSameDay($start->copy()->addDay()) && $end->hour === 0 && $end->minute === 0)) {
            return 'Eine Reservation muss am selben Tag enden.';
        }

        if ($startMin < $from || $endMin > $until) {
            return sprintf('Reservierbar ist von %s bis %s Uhr.', self::display($from), self::display($until));
        }

        $cafe = self::minutes(self::cafeFrom());
        if ($package->includesCafe() && $cafe !== null && $startMin < $cafe) {
            return sprintf('Das Café ist erst nach Betriebsschluss frei, ab %s Uhr. Wählt einen späteren Beginn oder einen Raum im Untergeschoss.', self::display($cafe));
        }

        if (RoomReservation::query()->confirmed()->overlapping($package, $start, $end)->exists()) {
            return 'Zu dieser Zeit ist mindestens ein Raum schon belegt. Bitte schaut im Belegungskalender nach einer freien Zeit.';
        }

        return null;
    }

    /** „18:00“ → 1080 */
    public static function minutes(mixed $time): ?int
    {
        if (! is_string($time) || ! preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $m)) {
            return null;
        }

        return (int) $m[1] * 60 + (int) $m[2];
    }

    /** 1080 → „18.00“ */
    public static function display(int $minutes): string
    {
        return sprintf('%02d.%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
