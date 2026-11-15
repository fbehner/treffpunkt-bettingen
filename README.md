# Treffpunkt Bettingen – Vereinswebsite

Website des Vereins Treffpunkt Bettingen: **treffpunkt-bettingen.ch**

- **Laravel 12** (PHP) – die Anwendung
- **Filament 4** – Website-Verwaltung unter `/admin`, Backoffice (Mitglieder) unter `/backoffice`
- **Tailwind CSS 4** – Gestaltung im Stil des Plakats „Ein Ort des aktiven Treffens.“
- Gehostet bei **green.ch** (Webhosting S, PHP + MySQL)

## Rollen

Ein Konto kann mehrere Rollen haben (vergeben vom Webadmin unter *Verwaltung → Benutzer*):

| Rolle | Bereich | Darf |
| --- | --- | --- |
| Webadmin | `/admin` | Benutzerkonten, Einstellungen, alle Inhalte |
| Redaktion | `/admin` | Seiten, Angebote, Aktuelles |
| Mitgliederverwaltung | `/backoffice` | Beitrittsgesuche, Mitglieder, Beiträge (Personendaten) |

Der Webadmin sieht **keine** Mitgliederdaten, ausser er hat zusätzlich die Rolle
Mitgliederverwaltung.

## Website-Verwaltung (`/admin`)

| Bereich | Wer | Inhalt |
| --- | --- | --- |
| Seiten | Redaktion, Admin | Startseite, Das Haus, Projekt, Verein, Impressum, Datenschutz – aus Bausteinen zusammengesetzt |
| Angebote | Redaktion, Admin | Die farbigen Kacheln „Das alles könnte hier stattfinden“ |
| Aktuelles | Redaktion, Admin | Neuigkeiten und Termine |
| Einstellungen | Redaktion, Admin | Hinweisband, Kontakt, Statuten-PDF, Mitgliederbeiträge |
| Benutzer | Webadmin | Zugänge, Rollen, deaktivieren |

## Backoffice (`/backoffice`)

| Bereich | Inhalt |
| --- | --- |
| Beitrittsgesuche | Online-Gesuche und von Hand erfasste (Papier, E-Mail, persönlich); „Aufnehmen“ erzeugt die Mitgliedschaft |
| Mitglieder | Mitgliedschaften mit Nummer, Personen (Stimmrecht ab 16), Eintritt/Austritt, Hinweis auf Altersübergang; Export Mitgliederliste und Stimmberechtigte für die GV |
| Beiträge | Jahresbeiträge erzeugen, als bezahlt markieren, Export für die Rechnungsstellung |

Datenmodell: `Membership` (Rechnungseinheit) → `MembershipPerson` (Personen) und
`MembershipFee` (ein Eintrag pro Jahr). `MembershipApplication` bleibt als Beleg.

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
app/Filament/               Website-Verwaltung (/admin)
app/Filament/Backoffice/    Backoffice (/backoffice): Gesuche, Mitglieder, Beiträge
app/Http/Controllers/       SiteController (Seiten, Aktuelles), MembershipController
app/Mail/                   E-Mails zum Beitrittsgesuch
resources/views/            page, blocks/, posts/, membership/, mail/, Layout, Symbole
resources/css/app.css       Farben und Schriften des Plakats
database/seeders/           Startinhalte aus den Projektunterlagen (+ assets/: Bilder, PDFs)
scripts/deploy.sh           Veröffentlichung per SSH/rsync
```
