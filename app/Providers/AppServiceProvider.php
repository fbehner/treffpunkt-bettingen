<?php

namespace App\Providers;

use App\Models\Page;
use Filament\Pages\BasePage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Speichern/Abbrechen bleiben am unteren Bildschirmrand sichtbar –
        // auch bei langen Formularen (z. B. Familie mit mehreren Personen).
        BasePage::stickyFormActions();


        // Menü- und Fusszeilen-Links für das Website-Layout.
        View::composer('components.layouts.site', function ($view) {
            $published = Page::query()->published()
                ->where('slug', '!=', Page::HOME_SLUG)
                ->get(['title', 'slug', 'show_in_nav', 'show_in_footer']);

            $view->with([
                'navPages' => $published->where('show_in_nav', true)->values(),
                'footerPages' => $published->where('show_in_footer', true)->values(),
            ]);
        });
    }
}
