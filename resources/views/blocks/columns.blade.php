<section class="py-16 sm:py-20" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        @include('blocks._heading')
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($data['items'] ?? [] as $item)
                @php($t = \App\Support\Ui::tone($item['tone'] ?? 'senf'))
                <div class="{{ $t['bg'] }} {{ $t['text'] }} flex min-h-52 flex-col gap-2.5 rounded-xl p-7">
                    @if (filled($item['label'] ?? null))
                        <p class="text-[13px] font-bold uppercase tracking-[0.12em] {{ $t['accent'] }}">{{ $item['label'] }}</p>
                    @endif
                    <h3 class="display text-[1.75rem] leading-tight">{{ $item['title'] ?? '' }}</h3>
                    @if (filled($item['text'] ?? null))
                        <p class="text-[1.05rem] leading-relaxed">{{ $item['text'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
