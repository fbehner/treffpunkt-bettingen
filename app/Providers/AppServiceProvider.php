<?php

namespace App\Providers;

use App\Models\Page;
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
        // Menü- und Fusszeilen-Links für das Website-Layout.
        View::composer('components.layouts.site', function ($view) {
            $published = Page::query()->published()->get(['title', 'slug', 'show_in_nav', 'show_in_footer']);

            $view->with([
                'navPages' => $published->where('show_in_nav', true)->values(),
                'footerPages' => $published->where('show_in_footer', true)->values(),
            ]);
        });
    }
}
