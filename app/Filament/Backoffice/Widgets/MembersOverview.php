<?php

namespace App\Filament\Backoffice\Widgets;

use App\Filament\Backoffice\Resources\MembershipApplications\MembershipApplicationResource;
use App\Filament\Backoffice\Resources\MembershipFees\MembershipFeeResource;
use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipFee;
use App\Models\MembershipPerson;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Übersicht auf der Startseite des Backoffice.
 */
class MembersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $active = Membership::query()->active();
        $year = (int) now()->year + (now()->month >= 11 ? 1 : 0);
        $openFees = MembershipFee::query()->where('year', $year)->whereNull('paid_at');
        $openApplications = MembershipApplication::query()->whereIn('status', ['eingegangen', 'bestaetigung'])->count();

        return [
            Stat::make('Aktive Mitgliedschaften', (clone $active)->count())
                ->description(MembershipPerson::query()->whereIn('membership_id', (clone $active)->select('id'))->count().' Personen')
                ->url(MembershipResource::getUrl('index')),
            Stat::make('Offene Gesuche', $openApplications)
                ->description($openApplications > 0 ? 'warten auf Entscheid des Vorstands' : 'alles bearbeitet')
                ->color($openApplications > 0 ? 'warning' : 'success')
                ->url(MembershipApplicationResource::getUrl('index')),
            Stat::make("Offene Beiträge {$year}", (clone $openFees)->count())
                ->description('CHF '.number_format((clone $openFees)->sum('amount'), 0, '.', '\''))
                ->color('danger')
                ->url(MembershipFeeResource::getUrl('index')),
        ];
    }
}
