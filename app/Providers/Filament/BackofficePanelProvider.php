<?php

namespace App\Providers\Filament;

use App\Filament\InitialsAvatarProvider;
use Filament\Actions\Action;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Backoffice unter /backoffice: Beitrittsgesuche, Mitglieder, Beiträge.
 * Zugang nur mit der Rolle „Mitgliederverwaltung“ (siehe User::PANEL_ROLES).
 */
class BackofficePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('backoffice')
            ->path('backoffice')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName('Treffpunkt · Backoffice')
            ->font('Inter', url: asset('fonts/inter.css'), provider: LocalFontProvider::class)
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->colors([
                // Dunkelgrün aus dem Plakat – unterscheidet das Backoffice von der Website-Verwaltung.
                'primary' => [
                    50 => 'oklch(0.975 0.012 175)',
                    100 => 'oklch(0.945 0.03 175)',
                    200 => 'oklch(0.89 0.055 175)',
                    300 => 'oklch(0.81 0.08 175)',
                    400 => 'oklch(0.68 0.095 175)',
                    500 => 'oklch(0.56 0.09 175)',
                    600 => 'oklch(0.47 0.075 175)',
                    700 => 'oklch(0.41 0.065 175)',
                    800 => 'oklch(0.35 0.055 175)',
                    900 => 'oklch(0.3 0.045 175)',
                    950 => 'oklch(0.22 0.035 175)',
                ],
                'gray' => Color::Stone,
            ])
            ->navigationGroups(['Mitglieder', 'Beiträge'])
            ->discoverResources(in: app_path('Filament/Backoffice/Resources'), for: 'App\Filament\Backoffice\Resources')
            ->discoverPages(in: app_path('Filament/Backoffice/Pages'), for: 'App\Filament\Backoffice\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Backoffice/Widgets'), for: 'App\Filament\Backoffice\Widgets')
            ->userMenuItems([
                Action::make('website-admin')
                    ->label('Website-Verwaltung')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url('/admin')
                    ->visible(fn () => auth()->user()?->canAccessPanel(filament()->getPanel('admin')) ?? false),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
