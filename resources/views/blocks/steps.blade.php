@php
    $items = $data['items'] ?? [];
    $bg = $data['background'] ?? 'creme';
    $dark = $bg === 'dunkel';
    $row = ($data['layout'] ?? 'row') === 'row';
@endphp
<section class="{{ \App\Support\Ui::section($bg) }} py-16 sm:py-20" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        @include('blocks._heading', ['kickerClass' => $dark ? 'text-senf' : 'text-rost'])

        @if ($row)
            <ol class="mt-10 grid gap-x-3 gap-y-6 sm:grid-cols-2 lg:grid-cols-{{ min(max(count($items), 1), 7) }}" role="list">
                @foreach ($items as $item)
                    @php($state = $item['state'] ?? 'done')
                    <li class="min-w-0 border-t-[3px] pt-4 hyphens-auto break-words {{ $state === 'current' ? 'border-senf' : ($state === 'planned' ? 'border-dashed '.($dark ? 'border-dunkel-weich' : 'border-dunkel/30') : ($dark ? 'border-dunkel-weich' : 'border-dunkel/40')) }}">
                        <p class="font-bold {{ $state === 'current' ? ($dark ? 'text-white' : 'text-rost') : ($dark ? 'text-senf' : 'text-dunkel') }} {{ $state === 'planned' ? 'opacity-80' : '' }}">{{ $item['date'] ?? '' }}</p>
                        <p class="mt-1.5 text-[15px] leading-snug {{ $state === 'planned' ? 'opacity-80' : '' }} {{ $state === 'current' && $dark ? 'text-white' : '' }}">
                            {{ $item['title'] ?? '' }}
                            @if ($state === 'current')
                                <span class="sr-only">(nächster Schritt)</span>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ol>
        @else
            <ol class="mt-10 max-w-3xl" role="list">
                @foreach ($items as $item)
                    @php($state = $item['state'] ?? 'done')
                    <li class="relative grid grid-cols-[1.5rem_1fr] gap-x-5 pb-8 last:pb-0">
                        <span class="relative z-10 mt-1 size-6 rounded-full border-4 {{ $state === 'current' ? 'border-rost bg-senf' : ($state === 'planned' ? 'border-dunkel/40 bg-creme' : 'border-gruen bg-gruen') }}" aria-hidden="true"></span>
                        @unless ($loop->last)
                            <span class="absolute top-8 bottom-1 left-[11px] w-0.5 bg-dunkel/20" aria-hidden="true"></span>
                        @endunless
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wider {{ $state === 'current' ? 'text-rost' : 'text-dunkel-weich' }}">{{ $item['date'] ?? '' }}@if ($state === 'current') · als Nächstes @elseif ($state === 'planned') · geplant @endif</p>
                            <h3 class="mt-1 text-xl font-semibold">{{ $item['title'] ?? '' }}</h3>
                            @if (filled($item['text'] ?? null))
                                <p class="mt-1.5 text-[1.05rem] leading-relaxed">{{ $item['text'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif

        @if (filled($data['link_label'] ?? null))
            <p class="mt-10"><a href="{{ $data['link_url'] ?? '#' }}" class="font-semibold {{ $dark ? 'text-white hover:text-senf' : '' }}">{{ $data['link_label'] }}</a></p>
        @endif
    </div>
</section>
