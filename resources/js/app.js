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
