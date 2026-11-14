# Treffpunkt Bettingen – Vereinswebsite

Website des Vereins Treffpunkt Bettingen: **treffpunkt-bettingen.ch**

- **Laravel 12** (PHP) – die Anwendung
- **Filament 4** – Verwaltungsoberfläche unter `/admin`
- **Tailwind CSS 4** – Gestaltung im Stil des Plakats „Ein Ort des aktiven Treffens.“
- Gehostet bei **green.ch** (Webhosting S, PHP + MySQL)

## Was die Verwaltung kann

| Bereich | Wer | Inhalt |
| --- | --- | --- |
| Startseite & Kontakt | Redaktion, Admin | Titel, Einleitung, Abschlusssatz, Kontakt, Fusszeile |
| Angebote | Redaktion, Admin | Die farbigen Kacheln: Titel, Text, Farbe, Symbol, Reihenfolge |
| Seiten | Redaktion, Admin | Freie Seiten (Über uns, Impressum, Datenschutz …) |
| Benutzer | nur Admin | Zugänge für Vereinsmitglieder, Rollen, deaktivieren |

Geplant: Mitgliederanmeldung, Raumreservierung.

## Lokal entwickeln

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed       # legt Startinhalte und admin@example.test / passwort123 an
npm run build                    # oder: npm run dev
php artisan serve
```

Website: http://127.0.0.1:8000 · Verwaltung: http://127.0.0.1:8000/admin

Tests: `php artisan test`

## Veröffentlichen

Siehe [docs/INSTALLATION.md](docs/INSTALLATION.md) für die einmalige Einrichtung,
danach:

```bash
scripts/deploy.sh
```

## Aufbau

```
app/Models/                 Offer (Kachel), Page (Seite), Setting (Texte), User
app/Filament/               Verwaltungsoberfläche
app/Http/Controllers/       SiteController – öffentliche Seiten
resources/views/            home, page, Layout, Symbole (components/symbol.blade.php)
resources/css/app.css       Farben und Schriften des Plakats
database/seeders/           Startinhalte nach dem Plakat
scripts/deploy.sh           Veröffentlichung per SSH/rsync
```
