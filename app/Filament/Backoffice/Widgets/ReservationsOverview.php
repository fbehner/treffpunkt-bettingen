<?php

namespace App\Filament\Backoffice\Widgets;

use App\Filament\Backoffice\Resources\RoomReservations\RoomReservationResource;
use App\Models\RoomReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Reservationen auf der Startseite des Backoffice.
 */
class ReservationsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->canManageReservations() ?? false;
    }

    protected function getStats(): array
    {
        $open = RoomReservation::query()->where('status', 'angefragt')->where('ends_at', '>=', now())->count();
        $week = RoomReservation::query()->confirmed()
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('starts_at', '<', now()->startOfDay()->addDays(7))
            ->count();
        $next = RoomReservation::query()->confirmed()->where('starts_at', '>=', now())->orderBy('starts_at')->first();
        $url = RoomReservationResource::getUrl('index');

        return [
            Stat::make('Offene Anfragen', $open)
                ->description($open > 0 ? 'warten auf Bestätigung' : 'alles bearbeitet')
                ->color($open > 0 ? 'warning' : 'success')
                ->url($url),
            Stat::make('Reservationen nächste 7 Tage', $week)
                ->description('bestätigt')
                ->url($url),
            Stat::make('Nächste Reservation', $next ? $next->starts_at->format('d.m.') : '–')
                ->description($next ? $next->packageInfo()->label().', '.$next->starts_at->format('H.i').' Uhr' : 'keine geplant')
                ->url($next ? RoomReservationResource::getUrl('edit', ['record' => $next]) : $url),
        ];
    }
}
