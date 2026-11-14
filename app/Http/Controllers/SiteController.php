<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('home', [
            's' => Setting::values(),
            'offers' => Offer::query()->visible()->get(),
        ]);
    }

    public function page(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('page', ['page' => $page]);
    }
}
