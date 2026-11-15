@php
    $blocks = $page->blocks ?? [];
    $startsWithHero = ($blocks[0]['type'] ?? null) === 'hero';
    $anchors = $page->anchors();
@endphp
<x-layouts.site :title="$page->isHome() ? null : $page->title" :description="$page->meta_description">
    @unless ($startsWithHero)
        <header class="container-site pt-8 pb-6 sm:pt-12">
            @if ($page->kicker)
                <p class="kicker text-rost">{{ $page->kicker }}</p>
            @endif
            <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">
                {{ $page->headline ?: $page->title }}
                @if ($page->headline_accent)
                    <span class="text-rost">{{ $page->headline_accent }}</span>
                @endif
            </h1>
            @if ($page->lead)
                <p class="mt-6 max-w-3xl text-xl leading-relaxed sm:text-[1.4rem]">{{ $page->lead }}</p>
            @endif
            @if (count($anchors) > 1)
                <nav aria-label="Auf dieser Seite" class="mt-8 flex flex-wrap gap-2.5">
                    @foreach ($anchors as $anchor)
                        <a href="#{{ $anchor['id'] }}" class="inline-flex min-h-11 items-center rounded-full bg-white px-4 font-semibold text-dunkel no-underline hover:bg-beige hover:text-dunkel">{{ $anchor['label'] }}</a>
                    @endforeach
                </nav>
            @endif
        </header>
    @endunless

    @if ($page->body)
        <div class="container-site pb-10">
            <div class="prose-site">{!! str($page->body)->sanitizeHtml() !!}</div>
        </div>
    @endif

    @foreach ($blocks as $block)
        @if (view()->exists('blocks.'.($block['type'] ?? '')))
            @include('blocks.'.$block['type'], ['data' => $block['data'] ?? [], 'page' => $page, 'loop' => $loop])
        @endif
    @endforeach
</x-layouts.site>
