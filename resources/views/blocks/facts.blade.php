<section class="py-8" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        @include('blocks._heading')
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 {{ filled($data['heading'] ?? null) ? 'mt-8' : '' }}">
            @foreach ($data['items'] ?? [] as $item)
                @php($t = \App\Support\Ui::tone($item['tone'] ?? 'senf'))
                <div class="{{ $t['bg'] }} {{ $t['text'] }} flex flex-col-reverse rounded-xl p-6">
                    <dt class="mt-2 text-[15px]">{{ $item['label'] ?? '' }}</dt>
                    <dd class="display text-[2.5rem] leading-none">{{ $item['value'] ?? '' }}</dd>
                </div>
            @endforeach
        </dl>
        @if (filled($data['note'] ?? null))
            <p class="mt-3 text-sm text-dunkel-weich">{{ $data['note'] }}</p>
        @endif
    </div>
</section>
