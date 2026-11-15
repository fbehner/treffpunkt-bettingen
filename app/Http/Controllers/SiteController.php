<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    public function home(): View
    {
        $page = Page::query()->where('slug', Page::HOME_SLUG)->firstOrFail();

        return view('page', ['page' => $page]);
    }

    public function page(string $slug): View|RedirectResponse
    {
        if ($slug === Page::HOME_SLUG) {
            return redirect()->route('home', status: 301);
        }

        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('page', ['page' => $page]);
    }

    public function posts(): View
    {
        return view('posts.index', [
            'upcoming' => Post::query()->upcoming()->get(),
            'posts' => Post::query()->visible()
                ->where(fn ($q) => $q->where('kind', 'news')->orWhere('event_starts_at', '<', now()->startOfDay()))
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->paginate(12),
        ]);
    }

    public function post(Post $post): View
    {
        abort_unless(Post::query()->visible()->whereKey($post->getKey())->exists(), 404);

        return view('posts.show', ['post' => $post]);
    }
}
