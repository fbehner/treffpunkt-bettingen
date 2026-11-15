<x-layouts.site title="Konto anlegen">
    <section class="container-site grid items-start gap-12 pt-10 pb-20 lg:grid-cols-[1.2fr_1fr]">
        <div>
            <p class="kicker text-rost">Einladung</p>
            <h1 class="display mt-4 text-[clamp(2.5rem,6vw,4.75rem)] leading-[0.95]">Konto <span class="text-rost">anlegen.</span></h1>
            <p class="mt-6 max-w-xl text-xl leading-relaxed">
                {{ $invitation->inviter?->name ?? 'Der Verein' }} hat dich eingeladen, bei der Verwaltung der Website mitzuarbeiten.
                Lege hier dein Passwort fest.
            </p>

            <form method="post" action="{{ route('invitation.store', $token) }}" class="mt-10 flex max-w-xl flex-col gap-5" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl border-2 border-rost-dunkel bg-white p-5" role="alert">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <label class="field" for="email">E-Mail
                    <input type="email" id="email" value="{{ $invitation->email }}" readonly aria-readonly="true" class="bg-beige!">
                    <small>Mit dieser Adresse meldest du dich später an.</small>
                </label>
                <label class="field" for="name">Vorname und Name
                    <input type="text" id="name" name="name" value="{{ old('name', $invitation->name) }}" autocomplete="name" required>
                </label>
                <label class="field" for="password">Passwort
                    <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required>
                    <small>Mindestens 10 Zeichen.</small>
                </label>
                <label class="field" for="password_confirmation">Passwort wiederholen
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                </label>
                <button type="submit" class="button mt-2 self-start px-8 py-4 text-lg">Konto anlegen</button>
            </form>
        </div>
        <aside class="rounded-2xl bg-white p-7">
            <p class="text-[17px] font-bold">So geht es weiter</p>
            <ol class="mt-3 list-decimal pl-5 leading-relaxed">
                <li>Du legst hier dein Konto an.</li>
                <li>Ein Webadmin erhält eine Meldung und weist dir deine Aufgaben zu – z. B. Redaktion der Website oder Mitgliederverwaltung.</li>
                <li>Du bekommst eine E-Mail, sobald du dich anmelden kannst.</li>
            </ol>
        </aside>
    </section>
</x-layouts.site>
