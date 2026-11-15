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
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName('Treffpunkt Bettingen')
            ->font('Inter', url: asset('fonts/inter.css'), provider: LocalFontProvider::class)
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->colors([
                // Rostrot aus dem Plakat (600 ≈ #B5472F)
                'primary' => [
                    50 => 'oklch(0.977 0.014 34)',
                    100 => 'oklch(0.95 0.033 34)',
                    200 => 'oklch(0.905 0.063 34)',
                    300 => 'oklch(0.84 0.106 34)',
                    400 => 'oklch(0.72 0.15 34)',
                    500 => 'oklch(0.62 0.165 34)',
                    600 => 'oklch(0.53 0.15 34)',
                    700 => 'oklch(0.46 0.13 34)',
                    800 => 'oklch(0.4 0.11 34)',
                    900 => 'oklch(0.35 0.09 34)',
                    950 => 'oklch(0.26 0.065 34)',
                ],
                'gray' => Color::Stone,
            ])
            ->navigationGroups(['Inhalte', 'Verwaltung'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->userMenuItems([
                Action::make('backoffice')
                    ->label('Backoffice (Mitglieder)')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->url('/backoffice')
                    ->visible(fn () => auth()->user()?->canAccessPanel(filament()->getPanel('backoffice')) ?? false),
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
