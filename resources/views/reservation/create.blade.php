@php
    use App\Support\RoomPackage;
    use App\Models\RoomReservation;

    $selected = old('package', request('raum', ''));
    $houseRules = \App\Support\Ui::file(\App\Models\Setting::get('house_rules_file'));
    $privacy = \App\Models\Page::query()->where('slug', 'datenschutz')->where('is_published', true)->exists() ? route('page.show', 'datenschutz') : null;
    $err = fn (string $f) => $errors->has($f) ? 'aria-invalid=true aria-describedby='.$f.'-error' : '';
    $roomColor = ['cafe' => 'bg-rost', 'gruppenraum' => 'bg-gruen', 'kinderraum' => 'bg-senf'];
    $rate = fn (int $chf) => $chf > 0 ? 'CHF '.$chf : 'auf Anfrage';

    // Kalender: Wochen ab Montag
    $gridStart = $month->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $gridEnd = $month->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
    $months = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $monthLabel = $months[$month->month].' '.$month->year;
@endphp
<x-layouts.site title="Räume reservieren" description="Café und Räume im Untergeschoss des Treffpunkts Bettingen reservieren – mit Belegungskalender.">
    <header class="container-site pt-8 pb-6 sm:pt-12">
        <p class="kicker text-rost">Raumreservation</p>
        <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">Räume <span class="text-rost">reservieren.</span></h1>
        <p class="mt-6 max-w-3xl text-xl leading-relaxed">Für Kurse, Sitzungen, Treffen, Familienfeste und Veranstaltungen: Schaut im Kalender, wann die Räume frei sind, und schickt uns eure Anfrage. Wir bestätigen per E-Mail.</p>
    </header>

    {{-- Belegungskalender --}}
    <section class="container-site pb-14" id="belegung" aria-labelledby="belegung-titel">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="display text-[1.9rem] leading-tight" id="belegung-titel">Belegung im {{ $monthLabel }}</h2>
            <nav class="flex gap-2" aria-label="Monat wechseln">
                @if ($prevMonth)
                    <a href="{{ route('reservation.create', ['monat' => $prevMonth->format('Y-m')]) }}#belegung" class="button-outline px-4 py-2" rel="nofollow">← <span class="sr-only">Vorheriger Monat:</span> {{ $months[$prevMonth->month] }}</a>
                @endif
                @if ($nextMonth)
                    <a href="{{ route('reservation.create', ['monat' => $nextMonth->format('Y-m')]) }}#belegung" class="button-outline px-4 py-2" rel="nofollow"><span class="sr-only">Nächster Monat:</span> {{ $months[$nextMonth->month] }} →</a>
                @endif
            </nav>
        </div>

        <ul class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-[15px]" aria-label="Legende">
            @foreach (RoomPackage::ROOMS as $key => $name)
                <li class="flex items-center gap-2"><span class="inline-block size-3 rounded-full {{ $roomColor[$key] }}" aria-hidden="true"></span>{{ $name }}</li>
            @endforeach
        </ul>

        <div class="mt-5 overflow-hidden rounded-2xl border-2 border-beige bg-white">
            <div class="hidden grid-cols-7 border-b-2 border-beige bg-beige/40 text-center text-sm font-bold md:grid" aria-hidden="true">
                @foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $wd)
                    <div class="py-2">{{ $wd }}</div>
                @endforeach
            </div>
            <ol class="grid md:grid-cols-7">
                @for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                    @php
                        $inMonth = $day->month === $month->month;
                        $entries = $inMonth ? ($byDay[$day->toDateString()] ?? collect()) : collect();
                        $past = $day->lt(now()->startOfDay());
                    @endphp
                    <li @class([
                        'border-beige p-2 md:min-h-28 md:border-r md:border-b md:[&:nth-child(7n)]:border-r-0',
                        'max-md:hidden' => ! $inMonth || $entries->isEmpty(),
                        'max-md:border-b' => $inMonth,
                        'bg-creme/50 text-dunkel-weich/60' => ! $inMonth || $past,
                    ])>
                        @if ($inMonth)
                            <p class="text-sm font-bold">
                                <span class="md:hidden">{{ RoomReservation::weekday($day) }}, </span>{{ $day->day }}.<span class="md:hidden"> {{ $months[$day->month] }}</span>
                            </p>
                            <ul class="mt-1 flex flex-col gap-1">
                                @foreach ($entries as $entry)
                                    <li class="rounded-md bg-creme px-2 py-1 text-[13px] leading-snug">
                                        <span class="mr-1 inline-flex gap-0.5 align-middle" aria-hidden="true">
                                            @foreach ($entry->packageInfo()->rooms() as $room)
                                                <span class="inline-block size-2.5 rounded-full {{ $roomColor[$room] }}"></span>
                                            @endforeach
                                        </span>
                                        <span class="font-semibold">{{ $entry->starts_at->format('H.i') }}–{{ $entry->ends_at->format('H.i') }}</span>
                                        <span class="block">{{ $entry->packageInfo()->label() }}: {{ $entry->publicLabel() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endfor
            </ol>
            @if ($byDay->isEmpty())
                <p class="p-4 text-dunkel-weich md:border-t-2 md:border-beige">Im {{ $monthLabel }} sind noch keine Reservationen bestätigt.</p>
            @endif
        </div>
        <p class="mt-3 text-sm text-dunkel-weich">Der Kalender zeigt bestätigte Reservationen. Offene Anfragen sind noch nicht eingetragen.</p>
    </section>

    {{-- Anfrage --}}
    <div class="container-site grid items-start gap-12 pb-20 lg:grid-cols-[1.6fr_1fr]" id="anfrage">
        <form method="post" action="{{ route('reservation.store') }}#anfrage" class="flex flex-col gap-10" data-reservation-form novalidate>
            @csrf
            <h2 class="display text-[2.4rem] leading-tight">Anfrage <span class="text-rost">senden.</span></h2>

            @if ($errors->any())
                <div class="rounded-xl border-2 border-rost-dunkel bg-white p-5" role="alert" tabindex="-1" data-error-summary>
                    <p class="font-bold text-rost-dunkel">Bitte prüft die markierten Angaben:</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->keys() as $key)
                            <li><a href="#{{ $key }}">{{ $errors->first($key) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Falle für Spam-Programme --}}
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">1. Raum</legend>
                <div class="grid gap-3 sm:grid-cols-2" id="package">
                    @foreach ($packages as $p)
                        <label class="relative flex min-h-11 cursor-pointer flex-col gap-1 rounded-xl border-2 border-beige bg-white p-4 has-[:checked]:border-rost has-[:checked]:shadow-[inset_0_0_0_2px_var(--color-rost)] has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-gruen">
                            <input type="radio" name="package" value="{{ $p->value }}" class="sr-only" @checked($selected === $p->value)
                                   data-package-radio data-label="{{ $p->label() }}" data-cafe="{{ $p->includesCafe() ? '1' : '0' }}"
                                   data-rate-member="{{ $p->hourlyRate(true) }}" data-rate-guest="{{ $p->hourlyRate(false) }}">
                            <span class="flex items-center gap-2 text-[17px] font-bold">
                                <span class="inline-flex gap-0.5" aria-hidden="true">
                                    @foreach ($p->rooms() as $room)
                                        <span class="inline-block size-3 rounded-full {{ $roomColor[$room] }}"></span>
                                    @endforeach
                                </span>
                                {{ $p->label() }}
                            </span>
                            <span class="text-sm text-dunkel-weich">{{ $p->hint() }}</span>
                            <span class="mt-1 text-sm">Pro Stunde: Mitglieder {{ $rate($p->hourlyRate(true)) }} · andere {{ $rate($p->hourlyRate(false)) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('package') <p class="text-sm font-semibold text-rost-dunkel" id="package-error">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">2. Datum und Zeit</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="field" for="date">Datum
                        <input type="date" id="date" name="date" value="{{ old('date') }}" min="{{ $earliest->toDateString() }}" max="{{ $latest->toDateString() }}" required {!! $err('date') !!}>
                    </label>
                    <label class="field" for="start">Beginn
                        <select id="start" name="start" required data-time-start {!! $err('start') !!}>
                            <option value="">–</option>
                            @foreach ($times as $t)
                                <option value="{{ $t }}" @selected(old('start') === $t)>{{ str_replace(':', '.', $t) }} Uhr</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field" for="end">Ende
                        <select id="end" name="end" required data-time-end {!! $err('end') !!}>
                            <option value="">–</option>
                            @foreach ($times as $t)
                                <option value="{{ $t }}" @selected(old('end') === $t)>{{ str_replace(':', '.', $t) }} Uhr</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                @foreach (['date', 'start', 'end'] as $f)
                    @error($f) <p class="text-sm font-semibold text-rost-dunkel" id="{{ $f }}-error">{{ $message }}</p> @enderror
                @endforeach
                <p class="text-sm text-dunkel-weich">
                    Frühestens ab {{ $earliest->format('d.m.Y') }}, Zeit inklusive Auf- und Abräumen.
                    @if ($cafeFrom)
                        <span data-cafe-hint>Das Café ist erst ab {{ str_replace(':', '.', $cafeFrom) }} Uhr frei.</span>
                    @endif
                </p>
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">3. Anlass</legend>
                <label class="field" for="purpose">Was habt ihr vor?
                    <input type="text" id="purpose" name="purpose" value="{{ old('purpose') }}" maxlength="200" required {!! $err('purpose') !!}>
                    <small>Zum Beispiel: Vorstandssitzung, Geburtstagsfest, Yoga-Kurs</small>
                    @error('purpose') <span class="error" id="purpose-error">{{ $message }}</span> @enderror
                </label>
                <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
                    <label class="field" for="public_title">Titel im öffentlichen Kalender
                        <input type="text" id="public_title" name="public_title" value="{{ old('public_title') }}" maxlength="80" {!! $err('public_title') !!}>
                        <small>Freiwillig. Leer lassen = dort steht nur „Belegt“. Bitte keine Namen von Privatpersonen.</small>
                        @error('public_title') <span class="error" id="public_title-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="field" for="participants">Anzahl Personen
                        <input type="number" id="participants" name="participants" value="{{ old('participants') }}" min="1" max="500" inputmode="numeric" {!! $err('participants') !!}>
                        <small>ungefähr</small>
                        @error('participants') <span class="error" id="participants-error">{{ $message }}</span> @enderror
                    </label>
                </div>
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">4. Kontakt und Rechnung</legend>
                <label class="field" for="organisation">Verein oder Organisation
                    <input type="text" id="organisation" name="organisation" value="{{ old('organisation') }}" autocomplete="organization" {!! $err('organisation') !!}>
                    <small>freiwillig</small>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field" for="first_name">Vorname
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required {!! $err('first_name') !!}>
                        @error('first_name') <span class="error" id="first_name-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="field" for="last_name">Name
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required {!! $err('last_name') !!}>
                        @error('last_name') <span class="error" id="last_name-error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <label class="field" for="street">Strasse und Nr.
                    <input type="text" id="street" name="street" value="{{ old('street') }}" autocomplete="street-address" required {!! $err('street') !!}>
                    @error('street') <span class="error" id="street-error">{{ $message }}</span> @enderror
                </label>
                <label class="field" for="zip_city">PLZ und Ort
                    <input type="text" id="zip_city" name="zip_city" value="{{ old('zip_city', '4126 Bettingen') }}" required {!! $err('zip_city') !!}>
                    @error('zip_city') <span class="error" id="zip_city-error">{{ $message }}</span> @enderror
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field" for="email">E-Mail
                        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required {!! $err('email') !!}>
                        @error('email') <span class="error" id="email-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="field" for="phone">Telefon
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" required {!! $err('phone') !!}>
                        <small>Für Rückfragen und die Schlüsselübergabe</small>
                        @error('phone') <span class="error" id="phone-error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="flex flex-col gap-3 rounded-2xl bg-beige p-5">
                    <label class="flex cursor-pointer items-start gap-3 text-[17px] leading-relaxed" for="is_member">
                        <input type="hidden" name="is_member" value="0">
                        <input type="checkbox" id="is_member" name="is_member" value="1" class="mt-1 size-5 flex-none accent-rost" @checked(old('is_member')) data-member-toggle>
                        <span>Wir sind Mitglied im Verein Treffpunkt Bettingen <span class="text-dunkel-weich">(Mitgliedertarif)</span></span>
                    </label>
                    <label class="field max-w-60" for="member_number" data-member-only>Mitgliedernummer
                        <input type="text" id="member_number" name="member_number" value="{{ old('member_number') }}" inputmode="numeric" {!! $err('member_number') !!}>
                        <small>falls zur Hand</small>
                    </label>
                    <p class="text-sm">Noch nicht Mitglied? <a href="{{ route('membership.create') }}">Mitglied werden</a></p>
                </div>
                <label class="field" for="message">Mitteilung
                    <textarea id="message" name="message" rows="3" {!! $err('message') !!}>{{ old('message') }}</textarea>
                    <small>Freiwillig – zum Beispiel, was ihr an Einrichtung braucht.</small>
                </label>
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">5. Bestätigung</legend>
                <label class="flex cursor-pointer items-start gap-3 text-[17px] leading-relaxed" for="house_rules">
                    <input type="checkbox" id="house_rules" name="house_rules" value="1" class="mt-1 size-5 flex-none accent-rost" @checked(old('house_rules')) {!! $err('house_rules') !!}>
                    <span>Wir halten uns an die @if ($houseRules)<a href="{{ $houseRules }}" target="_blank" rel="noopener">Hausordnung</a>@else Hausordnung @endif und geben die Räume so zurück, wie wir sie vorgefunden haben.</span>
                </label>
                @error('house_rules') <p class="-mt-2 text-sm font-semibold text-rost-dunkel" id="house_rules-error">{{ $message }}</p> @enderror
                <label class="flex cursor-pointer items-start gap-3 text-[17px] leading-relaxed" for="privacy">
                    <input type="checkbox" id="privacy" name="privacy" value="1" class="mt-1 size-5 flex-none accent-rost" @checked(old('privacy')) {!! $err('privacy') !!}>
                    <span>Ich bin einverstanden, dass meine Angaben für die Reservation gespeichert werden{!! $privacy ? ' (<a href="'.e($privacy).'" target="_blank" rel="noopener">Datenschutz</a>)' : '' !!}.</span>
                </label>
                @error('privacy') <p class="-mt-2 text-sm font-semibold text-rost-dunkel" id="privacy-error">{{ $message }}</p> @enderror
                <button type="submit" class="button mt-2 self-start px-8 py-4 text-lg">Anfrage senden</button>
            </fieldset>
        </form>

        <aside class="flex flex-col gap-4 lg:sticky lg:top-6">
            <div class="rounded-2xl bg-dunkel p-7 text-creme" aria-live="polite">
                <p class="text-[13px] font-bold uppercase tracking-[0.12em] text-senf">Eure Anfrage</p>
                <p class="display mt-3 text-[1.9rem] text-white" data-summary-label>Raum wählen</p>
                <p class="mt-1 text-lg" data-summary-time></p>
                <p class="mt-1 text-lg" data-summary-price></p>
                <div class="my-5 h-px bg-dunkel-weich"></div>
                <p class="leading-relaxed">{{ \App\Models\Setting::get('reservation_note') }}</p>
            </div>
        </aside>
    </div>
</x-layouts.site>
