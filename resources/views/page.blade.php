<x-layouts.site :title="$page->title">
    <article class="container-site pt-8 sm:pt-14">
        <h1 class="display text-[clamp(2.75rem,8vw,5.5rem)] leading-[0.95]">{{ $page->title }}</h1>
        <div class="mt-4 h-[3px] w-24 bg-rost" aria-hidden="true"></div>
        @if ($page->body)
            <div class="prose-site mt-10">
                {!! str($page->body)->sanitizeHtml() !!}
            </div>
        @endif
    </article>
</x-layouts.site>
