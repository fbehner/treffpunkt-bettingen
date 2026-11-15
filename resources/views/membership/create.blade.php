@php
    $selected = old('type', request('art', 'einzel'));
    $statutes = \App\Support\Ui::file(\App\Models\Setting::get('statutes_file'));
    $privacy = \App\Models\Page::query()->where('slug', 'datenschutz')->where('is_published', true)->exists() ? route('page.show', 'datenschutz') : null;
    $err = fn (string $f) => $errors->has($f) ? 'aria-invalid=true aria-describedby='.$f.'-error' : '';
@endphp
<x-layouts.site title="Mitglied werden" description="Beitrittsgesuch für den Verein Treffpunkt Bettingen – für Einwohnerinnen, Einwohner, Vereine und Institutionen.">
    <header class="container-site pt-8 pb-6 sm:pt-12">
        <p class="kicker text-rost">Beitrittsgesuch</p>
        <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">Mitglied <span class="text-rost">werden.</span></h1>
        <p class="mt-6 max-w-3xl text-xl leading-relaxed">Mit eurem Beitrag unterstützt ihr den Verein in seiner Aufgabe, einen Treffpunkt mit und für Bettingen zu führen – als Einwohnerin und Einwohner ebenso wie als Verein oder Institution.</p>
    </header>

    <div class="container-site grid items-start gap-12 pb-20 lg:grid-cols-[1.6fr_1fr]">
        <form method="post" action="{{ route('membership.store') }}" class="flex flex-col gap-10" data-membership-form novalidate>
            @csrf

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
                <legend class="display mb-4 text-[1.75rem]">1. Art der Mitgliedschaft</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" id="type">
                    @foreach ($types as $t)
                        <label class="relative flex min-h-11 cursor-pointer flex-col gap-1 rounded-xl border-2 border-beige bg-white p-4 has-[:checked]:border-rost has-[:checked]:shadow-[inset_0_0_0_2px_var(--color-rost)] has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-gruen">
                            <input type="radio" name="type" value="{{ $t->value }}" class="sr-only" @checked($selected === $t->value) data-type-radio data-fee="{{ $t->fee() }}" data-label="{{ $t->label() }}">
                            <span class="text-[17px] font-bold">{{ $t->label() }}</span>
                            <span class="display text-[1.6rem] text-rost">CHF {{ $t->fee() }}</span>
                            <span class="text-sm text-dunkel-weich">{{ $t->hint() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('type') <p class="text-sm font-semibold text-rost-dunkel" id="type-error">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">2. Angaben</legend>

                <div data-show-for="verein" class="flex flex-col gap-4">
                    <label class="field" for="organisation">Name des Vereins oder der Institution
                        <input type="text" id="organisation" name="organisation" value="{{ old('organisation') }}" autocomplete="organization" {!! $err('organisation') !!}>
                        @error('organisation') <span class="error" id="organisation-error">{{ $message }}</span> @enderror
                    </label>
                    <p class="text-dunkel-weich">Kontaktperson:</p>
                </div>

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

                <div data-show-for="jugend kind">
                    <label class="field max-w-72" for="birthdate">Geburtsdatum
                        <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" autocomplete="bday" {!! $err('birthdate') !!}>
                        <small>Kind: 12 bis 15 Jahre · Jugendliche/r: 16 bis 17 Jahre</small>
                        @error('birthdate') <span class="error" id="birthdate-error">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div data-show-for="paar familie" class="flex flex-col gap-3">
                    <p class="mt-2 font-bold">Zweite Person <span class="font-normal text-dunkel-weich" data-show-for="familie">(optional)</span></p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="field" for="partner_first_name">Vorname
                            <input type="text" id="partner_first_name" name="partner_first_name" value="{{ old('partner_first_name') }}" {!! $err('partner_first_name') !!}>
                            @error('partner_first_name') <span class="error" id="partner_first_name-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="field" for="partner_last_name">Name
                            <input type="text" id="partner_last_name" name="partner_last_name" value="{{ old('partner_last_name') }}" {!! $err('partner_last_name') !!}>
                            @error('partner_last_name') <span class="error" id="partner_last_name-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </div>

                <div data-show-for="familie">
                    <label class="field" for="children">Kinder im Haushalt
                        <textarea id="children" name="children" rows="3" {!! $err('children') !!}>{{ old('children') }}</textarea>
                        <small>Vorname und Jahrgang, je eine Zeile</small>
                    </label>
                </div>

                <label class="field" for="street">Strasse und Nr.
                    <input type="text" id="street" name="street" value="{{ old('street') }}" autocomplete="street-address" required {!! $err('street') !!}>
                    @error('street') <span class="error" id="street-error">{{ $message }}</span> @enderror
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field" for="zip_city">PLZ und Ort
                        <input type="text" id="zip_city" name="zip_city" value="{{ old('zip_city', '4126 Bettingen') }}" required {!! $err('zip_city') !!}>
                        @error('zip_city') <span class="error" id="zip_city-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="field" for="phone">Telefon
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" {!! $err('phone') !!}>
                        <small>freiwillig</small>
                    </label>
                </div>
                <label class="field" for="email">E-Mail
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required {!! $err('email') !!}>
                    <small>Für die Bestätigung und die Rechnung</small>
                    @error('email') <span class="error" id="email-error">{{ $message }}</span> @enderror
                </label>
            </fieldset>

            <fieldset class="flex flex-col gap-4 rounded-2xl bg-beige p-6" data-show-for="jugend kind">
                <legend class="display float-left mb-2 w-full text-[1.75rem]">Erziehungsberechtigte Person</legend>
                <p class="leading-relaxed">Bei Minderjährigen bestätigt eine erziehungsberechtigte Person das Gesuch. Sie erhält dazu eine E-Mail mit einem Bestätigungslink – das ersetzt die Unterschrift.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field" for="guardian_name">Vorname und Name
                        <input type="text" id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}" {!! $err('guardian_name') !!}>
                        @error('guardian_name') <span class="error" id="guardian_name-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="field" for="guardian_email">E-Mail
                        <input type="email" id="guardian_email" name="guardian_email" value="{{ old('guardian_email') }}" {!! $err('guardian_email') !!}>
                        @error('guardian_email') <span class="error" id="guardian_email-error">{{ $message }}</span> @enderror
                    </label>
                </div>
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">3. Gönnerbeitrag <span class="font-sans text-base font-normal text-dunkel-weich">(freiwillig)</span></legend>
                <label class="field max-w-80" for="donation">Betrag in CHF
                    <input type="number" id="donation" name="donation" min="0" step="1" inputmode="numeric" value="{{ old('donation') }}" {!! $err('donation') !!}>
                    <small>Zusätzlich zum Mitgliederbeitrag</small>
                    @error('donation') <span class="error" id="donation-error">{{ $message }}</span> @enderror
                </label>
                <label class="field" for="message">Mitteilung an den Vorstand
                    <textarea id="message" name="message" rows="3" {!! $err('message') !!}>{{ old('message') }}</textarea>
                    <small>Freiwillig – zum Beispiel, wo ihr gerne mithelfen würdet.</small>
                </label>
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="display mb-4 text-[1.75rem]">4. Bestätigung</legend>
                <label class="flex cursor-pointer items-start gap-3 text-[17px] leading-relaxed" for="statutes">
                    <input type="checkbox" id="statutes" name="statutes" value="1" class="mt-1 size-5 flex-none accent-rost" @checked(old('statutes')) {!! $err('statutes') !!}>
                    <span>Ich habe die @if ($statutes)<a href="{{ $statutes }}" target="_blank" rel="noopener">Statuten</a>@else Statuten @endif gelesen und bin damit einverstanden.</span>
                </label>
                @error('statutes') <p class="-mt-2 text-sm font-semibold text-rost-dunkel" id="statutes-error">{{ $message }}</p> @enderror
                <label class="flex cursor-pointer items-start gap-3 text-[17px] leading-relaxed" for="privacy">
                    <input type="checkbox" id="privacy" name="privacy" value="1" class="mt-1 size-5 flex-none accent-rost" @checked(old('privacy')) {!! $err('privacy') !!}>
                    <span>Ich bin einverstanden, dass meine Angaben für die Mitgliederverwaltung gespeichert werden{!! $privacy ? ' (<a href="'.e($privacy).'" target="_blank" rel="noopener">Datenschutz</a>)' : '' !!}.</span>
                </label>
                @error('privacy') <p class="-mt-2 text-sm font-semibold text-rost-dunkel" id="privacy-error">{{ $message }}</p> @enderror
                <button type="submit" class="button mt-2 self-start px-8 py-4 text-lg">Beitrittsgesuch senden</button>
            </fieldset>
        </form>

        <aside class="flex flex-col gap-4 lg:sticky lg:top-6">
            <div class="rounded-2xl bg-dunkel p-7 text-creme" aria-live="polite">
                <p class="text-[13px] font-bold uppercase tracking-[0.12em] text-senf">Eure Auswahl</p>
                <p class="display mt-3 text-[1.9rem] text-white" data-summary-label>{{ \App\Support\MembershipType::tryFrom($selected)?->label() ?? 'Einzelperson' }}</p>
                <p class="mt-1 text-lg">CHF <span data-summary-fee>{{ \App\Support\MembershipType::tryFrom($selected)?->fee() ?? \App\Support\MembershipType::Einzel->fee() }}</span> pro Jahr</p>
                <div class="my-5 h-px bg-dunkel-weich"></div>
                <p class="leading-relaxed">{{ \App\Models\Setting::get('membership_note') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6">
                <p class="text-[17px] font-bold">Was die Mitgliedschaft bringt</p>
                <ul class="mt-2 list-disc pl-5 leading-relaxed">
                    <li>Räume zu vergünstigten Tarifen nutzen</li>
                    <li>Vorrang bei Reservationen</li>
                    <li>Stimmrecht an der Generalversammlung ab 16 Jahren</li>
                </ul>
                <p class="mt-3 text-sm text-dunkel-weich">Über die Aufnahme entscheidet der Vorstand.</p>
            </div>
        </aside>
    </div>
</x-layouts.site>
