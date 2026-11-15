@php
    $items = $data['items'] ?? [];
    $bg = $data['background'] ?? 'creme';
    $card = $bg === 'creme' ? 'bg-white' : ($bg === 'dunkel' ? 'bg-white/5' : 'bg-white');
@endphp
<section class="{{ \App\Support\Ui::section($bg) }} py-16 sm:py-20" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        @include('blocks._heading', ['kickerClass' => $bg === 'beige' ? 'text-rost-dunkel' : 'text-gruen'])
        @if (filled($data['intro'] ?? null))
            <p class="mt-5 max-w-3xl text-xl leading-relaxed">{{ $data['intro'] }}</p>
        @endif
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            @foreach ($items as $item)
                @php($img = \App\Support\Ui::file($item['image'] ?? null))
                <article class="{{ $card }} flex flex-col overflow-hidden rounded-2xl">
                    @if ($img)
                        <img src="{{ $img }}" alt="{{ $item['image_alt'] ?? '' }}" loading="lazy" class="aspect-video w-full object-cover">
                    @endif
                    <div class="flex flex-1 flex-col gap-3 p-7 sm:p-8">
                        <h3 class="display text-[2rem] leading-tight">{{ $item['title'] ?? '' }}</h3>
                        @if (filled($item['text'] ?? null))
                            <p class="text-lg leading-relaxed">{{ $item['text'] }}</p>
                        @endif
                        @if (filled($item['link_label'] ?? null))
                            <p class="mt-auto pt-2"><a href="{{ $item['link_url'] ?? '#' }}" class="font-semibold">{{ $item['link_label'] }}</a></p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
