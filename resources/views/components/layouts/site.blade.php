@props(['title' => null, 'description' => null])
@php($s = \App\Models\Setting::values())
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' – '.$s['site_name'] : $s['site_name'] }}</title>
    <meta name="description" content="{{ $description ?? $s['intro'] }}">
    <meta name="theme-color" content="#f3eadf">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#inhalt" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-dunkel focus:px-4 focus:py-2 focus:text-white">Zum Inhalt springen</a>

    <header class="container-site flex flex-wrap items-center justify-between gap-x-6 gap-y-3 py-5">
        <a href="{{ route('home') }}" class="flex items-center gap-3 text-dunkel no-underline hover:text-dunkel">
            <x-symbol name="cup" class="size-7 text-rost" />
            <span class="kicker">{{ $s['site_name'] }}</span>
        </a>

        @if ($navPages->isNotEmpty())
            <button type="button" class="-mr-2 inline-flex size-11 items-center justify-center rounded-lg md:hidden" data-nav-toggle aria-expanded="false" aria-controls="hauptmenu" aria-label="Menü">
                <x-symbol name="menu" class="size-7" />
            </button>
            <nav id="hauptmenu" data-nav aria-label="Hauptmenü" class="w-full flex-col gap-1 md:w-auto md:flex-row md:gap-6">
                @foreach ($navPages as $navPage)
                    <a href="{{ route('page.show', $navPage->slug) }}"
                       @if (request()->is($navPage->slug)) aria-current="page" @endif
                       class="py-2 font-medium text-dunkel no-underline hover:text-rost aria-[current=page]:text-rost">{{ $navPage->title }}</a>
                @endforeach
            </nav>
        @endif
    </header>

    <main id="inhalt" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-24 bg-dunkel text-creme">
        <div class="container-site grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="display text-2xl text-white">{{ $s['site_name'] }}</p>
                @if ($s['footer_text'])
                    <p class="mt-3 max-w-xs text-creme/80">{{ $s['footer_text'] }}</p>
                @endif
            </div>
            @if ($s['address'] || $s['contact_email'])
                <div>
                    <p class="kicker text-senf">Kontakt</p>
                    @if ($s['address'])
                        <p class="mt-3 whitespace-pre-line">{{ $s['address'] }}</p>
                    @endif
                    @if ($s['contact_email'])
                        <p class="mt-3"><a href="mailto:{{ $s['contact_email'] }}" class="text-creme hover:text-white">{{ $s['contact_email'] }}</a></p>
                    @endif
                </div>
            @endif
            @if ($footerPages->isNotEmpty())
                <nav aria-label="Rechtliches" class="flex flex-col items-start gap-2">
                    @foreach ($footerPages as $footerPage)
                        <a href="{{ route('page.show', $footerPage->slug) }}" class="text-creme hover:text-white">{{ $footerPage->title }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </footer>
</body>
</html>
