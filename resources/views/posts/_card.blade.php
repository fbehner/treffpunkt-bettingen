@php
    $date = $post->displayDate();
    $img = \App\Support\Ui::file($post->image);
@endphp
<article class="flex flex-col overflow-hidden rounded-2xl bg-white">
    @if ($img)
        <img src="{{ $img }}" alt="{{ $post->image_alt ?? '' }}" loading="lazy" class="aspect-video w-full object-cover">
    @endif
    <div class="flex flex-1 flex-col gap-2 p-6">
        <p class="text-sm font-semibold text-dunkel-weich">
            <span class="mr-1 rounded bg-beige px-1.5 py-0.5 text-xs font-bold uppercase tracking-wider text-dunkel">{{ \App\Models\Post::KINDS[$post->kind] ?? '' }}</span>
            @if ($date)
                <time datetime="{{ $date->toIso8601String() }}">{{ $date->translatedFormat($post->isEvent() ? 'l, j. F Y' : 'j. F Y') }}</time>
            @endif
        </p>
        <h3 class="display text-2xl leading-tight">
            <a href="{{ route('posts.show', $post->slug) }}" class="text-dunkel no-underline hover:text-rost">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="leading-relaxed">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
