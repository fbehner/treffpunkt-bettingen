<section class="{{ \App\Support\Ui::section($data['background'] ?? null) }} py-14 sm:py-16" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        @include('blocks._heading')
        @if (filled($data['body'] ?? null))
            <div class="prose-site {{ filled($data['heading'] ?? null) ? 'mt-6' : '' }}">{!! str($data['body'])->sanitizeHtml() !!}</div>
        @endif
    </div>
</section>
