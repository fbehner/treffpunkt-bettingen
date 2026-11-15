@php($types = \App\Support\MembershipType::cases())
<section class="py-16 sm:py-20" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site grid items-start gap-12 lg:grid-cols-2">
        <div>
            @include('blocks._heading')
            @if (filled($data['body'] ?? null))
                <div class="prose-site mt-7">{!! str($data['body'])->sanitizeHtml() !!}</div>
            @endif
        </div>
        <div class="flex flex-col gap-5 rounded-2xl bg-rost p-8 text-white sm:p-9">
            <h3 class="display text-4xl leading-tight">{{ $data['box_heading'] ?? 'Mitglied werden' }}</h3>
            @if (filled($data['box_text'] ?? null))
                <p class="text-lg leading-relaxed">{{ $data['box_text'] }}</p>
            @endif
            <dl class="divide-y divide-white/35 text-[1.05rem]">
                @foreach ($types as $type)
                    <div class="flex justify-between gap-4 py-2">
                        <dt>{{ $type->label() }} <span class="text-white/80">· {{ $type->hint() }}</span></dt>
                        <dd class="font-semibold whitespace-nowrap">CHF {{ $type->fee() }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="text-[15px] leading-relaxed text-white/90">Jahresbeiträge. {{ \App\Models\Setting::get('membership_note') }}</p>
            <a href="{{ route('membership.create') }}" class="mt-1 inline-flex min-h-11 items-center self-start rounded-lg bg-white px-6 py-3 font-bold text-rost-dunkel no-underline hover:bg-creme hover:text-rost-dunkel">{{ $data['button_label'] ?? 'Beitrittsgesuch ausfüllen' }}</a>
        </div>
    </div>
</section>
