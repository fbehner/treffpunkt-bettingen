# Treffpunkt Bettingen – Vereinswebsite

Website des Vereins Treffpunkt Bettingen: **treffpunkt-bettingen.ch**

- **Laravel 12** (PHP) – die Anwendung
- **Filament 4** – Verwaltungsoberfläche unter `/admin`
- **Tailwind CSS 4** – Gestaltung im Stil des Plakats „Ein Ort des aktiven Treffens.“
- Gehostet bei **green.ch** (Webhosting S, PHP + MySQL)

## Was die Verwaltung kann

| Bereich | Wer | Inhalt |
| --- | --- | --- |
| Seiten | Redaktion, Admin | Startseite, Das Haus, Projekt, Verein, Impressum, Datenschutz – aus Bausteinen zusammengesetzt |
| Angebote | Redaktion, Admin | Die farbigen Kacheln „Das alles könnte hier stattfinden“ |
| Aktuelles | Redaktion, Admin | Neuigkeiten und Termine |
| Einstellungen | Redaktion, Admin | Hinweisband, Kontakt, Statuten-PDF, Mitgliederbeiträge |
| Beitrittsgesuche | nur Admin | Online-Gesuche, Status, Export als CSV |
| Benutzer | nur Admin | Zugänge für Vereinsmitglieder, Rollen, deaktivieren |

### Bausteine für Seiten

Titelbereich mit Bild · Text · Bild mit Text · Karten mit Bild · Farbige Felder ·
Zahlen & Fakten · Zeitstrahl · Angebots-Kacheln · Verein & Mitgliedschaft ·
Hinweis mit Knopf · Download. Vorlagen in `resources/views/blocks/`, Formular in
`app/Filament/Resources/Pages/Schemas/PageBlocks.php`.

### Beitrittsgesuch (`/mitglied-werden`)

Mitgliedschaftsarten in `app/Support/MembershipType.php` (Kind 12–15, Jugend
16–17). Bei Minderjährigen bestätigt eine erziehungsberechtigte Person per
E-Mail-Link. E-Mails gehen an die antragstellende Person und an die
Kontakt-E-Mail aus den Einstellungen.

Geplant: Raumreservierung.

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
app/Models/                 Offer, Page (mit Bausteinen), Post, MembershipApplication, Setting, User
app/Filament/               Verwaltungsoberfläche
app/Http/Controllers/       SiteController (Seiten, Aktuelles), MembershipController
app/Mail/                   E-Mails zum Beitrittsgesuch
resources/views/            page, blocks/, posts/, membership/, mail/, Layout, Symbole
resources/css/app.css       Farben und Schriften des Plakats
database/seeders/           Startinhalte aus den Projektunterlagen (+ assets/: Bilder, PDFs)
scripts/deploy.sh           Veröffentlichung per SSH/rsync
```
