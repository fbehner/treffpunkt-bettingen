// Mobiles Menü ein-/ausklappen.
const toggle = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');

if (toggle && nav) {
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!open));
        nav.classList.toggle('is-open', !open);
    });
}

// Beitrittsgesuch: nur die Felder zur gewählten Mitgliedschaft zeigen.
const form = document.querySelector('[data-membership-form]');

if (form) {
    const radios = form.querySelectorAll('[data-type-radio]');
    const sections = form.querySelectorAll('[data-show-for]');
    const label = document.querySelector('[data-summary-label]');
    const fee = document.querySelector('[data-summary-fee]');

    const update = () => {
        const checked = form.querySelector('[data-type-radio]:checked');
        if (!checked) return;
        const type = checked.value;

        sections.forEach((el) => {
            const show = el.dataset.showFor.split(' ').includes(type);
            el.hidden = !show;
            // Versteckte Felder nicht mitsenden.
            el.querySelectorAll('input, textarea').forEach((input) => {
                input.disabled = !show;
            });
        });

        if (label) label.textContent = checked.dataset.label;
        if (fee) fee.textContent = checked.dataset.fee;
    };

    radios.forEach((radio) => radio.addEventListener('change', update));
    update();

    form.querySelector('[data-error-summary]')?.focus();
}

// Raumreservation: Zusammenfassung mit Dauer und voraussichtlichem Preis.
const reservation = document.querySelector('[data-reservation-form]');

if (reservation) {
    const start = reservation.querySelector('[data-time-start]');
    const end = reservation.querySelector('[data-time-end]');
    const member = reservation.querySelector('[data-member-toggle]');
    const memberOnly = reservation.querySelector('[data-member-only]');
    const cafeHint = reservation.querySelector('[data-cafe-hint]');
    const label = document.querySelector('[data-summary-label]');
    const time = document.querySelector('[data-summary-time]');
    const price = document.querySelector('[data-summary-price]');

    const minutes = (value) => {
        const [h, m] = value.split(':').map(Number);
        return h * 60 + m;
    };
    const hours = (min) => (min / 60).toLocaleString('de-CH', { maximumFractionDigits: 1 });

    const update = () => {
        const pkg = reservation.querySelector('[data-package-radio]:checked');
        const isMember = member?.checked ?? false;

        if (memberOnly) memberOnly.hidden = !isMember;
        if (cafeHint) cafeHint.classList.toggle('font-semibold', pkg?.dataset.cafe === '1');
        if (label) label.textContent = pkg ? pkg.dataset.label : 'Raum wählen';

        const duration = start.value && end.value ? minutes(end.value) - minutes(start.value) : 0;
        if (time) time.textContent = duration > 0 ? `${hours(duration)} Stunden` : '';

        if (!price) return;
        if (!pkg || duration <= 0) {
            price.textContent = '';
            return;
        }
        const rate = Number(isMember ? pkg.dataset.rateMember : pkg.dataset.rateGuest);
        price.textContent = rate > 0
            ? `Voraussichtlich CHF ${Math.round((rate * duration) / 60)} (${isMember ? 'Mitgliedertarif' : 'Tarif für Nichtmitglieder'})`
            : 'Preis nach Absprache';
    };

    reservation.addEventListener('change', update);
    update();

    reservation.querySelector('[data-error-summary]')?.focus();
}
