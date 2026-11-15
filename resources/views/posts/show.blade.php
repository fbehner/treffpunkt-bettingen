@php($img = \App\Support\Ui::file($post->image))
<x-layouts.site :title="$post->title" :description="$post->excerpt">
    <article class="container-site pt-8 pb-20 sm:pt-12">
        <p><a href="{{ route('posts.index') }}" class="font-semibold">← Aktuelles</a></p>
        <p class="kicker mt-8 text-rost">{{ \App\Models\Post::KINDS[$post->kind] ?? '' }}</p>
        <h1 class="display mt-3 max-w-4xl text-[clamp(2.5rem,6vw,4.75rem)] leading-[0.98]">{{ $post->title }}</h1>

        @if ($post->isEvent())
            <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-3 rounded-2xl bg-white p-6 text-lg">
                <div>
                    <dt class="text-sm font-semibold uppercase tracking-wider text-dunkel-weich">Wann</dt>
                    <dd class="font-semibold">
                        {{ $post->event_starts_at->translatedFormat('l, j. F Y') }}
                        @if ($post->event_starts_at->format('H:i') !== '00:00')
                            , {{ $post->event_starts_at->format('H.i') }} Uhr
                        @endif
                    </dd>
                </div>
                @if ($post->event_location)
                    <div>
                        <dt class="text-sm font-semibold uppercase tracking-wider text-dunkel-weich">Wo</dt>
                        <dd class="font-semibold">{{ $post->event_location }}</dd>
                    </div>
                @endif
            </dl>
        @elseif ($post->displayDate())
            <p class="mt-4 text-dunkel-weich">{{ $post->displayDate()->translatedFormat('j. F Y') }}</p>
        @endif

        @if ($post->excerpt)
            <p class="mt-8 max-w-3xl text-xl leading-relaxed sm:text-[1.35rem]">{{ $post->excerpt }}</p>
        @endif
        @if ($img)
            <img src="{{ $img }}" alt="{{ $post->image_alt ?? '' }}" class="mt-10 aspect-video w-full max-w-4xl rounded-2xl object-cover">
        @endif
        @if ($post->body)
            <div class="prose-site mt-10">{!! str($post->body)->sanitizeHtml() !!}</div>
        @endif
    </article>
</x-layouts.site>
