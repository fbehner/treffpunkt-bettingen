# Hinweise für Claude

Vereinswebsite **treffpunkt-bettingen.ch**. Der Betreuer (Frank) lässt die Website
über Claude pflegen; weitere Vereinsmitglieder bearbeiten Inhalte über `/admin`.

## Sprache und Ton
- Alles für Nutzer ist **Deutsch (Schweiz)**: „ss“ statt „ß“ (Fusszeile, gross).
- Code-Kommentare deutsch, kurz.

## Stack
- Laravel 12, Filament 4, Tailwind 4, PHP ≥ 8.2.
- Hosting: green.ch **Webhosting S** – nur PHP + MySQL, SSH ja, **kein Git, kein
  Node.js auf dem Server**, 4 Cronjobs. Deshalb: lokal bauen, per
  `scripts/deploy.sh` (rsync) hochladen. Keine Queues/Worker voraussetzen
  (`QUEUE_CONNECTION=sync`).
- Lokal SQLite, auf dem Server MySQL – Migrationen müssen auf beiden laufen.

## Gestaltung (vom Plakat)
- Farben in `resources/css/app.css` (`@theme`): creme, beige, rost, senf, gruen,
  dunkel. Schriften: Fraunces 900 (Titel, Utility `display`), Inter (Text).
- Schriften lokal ausgeliefert (keine Google Fonts, kein ui-avatars.com –
  Datenschutz). Keine externen Dienste ohne Rückfrage einbinden.
- Neue Kachel-Symbole: Pfad in `resources/views/components/symbol.blade.php`
  und Eintrag in `App\Models\Offer::ICONS`.
- Blade-Komponente heisst `<x-symbol>` (nicht `x-icon`, das ist von blade-icons belegt).

## Inhalte
- Inhalte gehören in die Datenbank (editierbar in `/admin`), nicht hart in Views.
- Seiten bestehen aus Bausteinen (`Page.blocks`, Filament Builder). Neuer
  Baustein: Block in `PageBlocks.php` + Vorlage `resources/views/blocks/<typ>.blade.php`.
- Startseite = Seite mit Slug `startseite` (Route `/`).
- Hochgeladene Dateien liegen auf der Disk `public` (`bilder/`, `dokumente/`),
  Ausgabe über `App\Support\Ui::file()`.
- Mitgliedschaftsarten und Altersgrenzen: `App\Support\MembershipType`;
  Beiträge in den Einstellungen. Gesuche (Personendaten) nur für Admins.
- E-Mail-Versand nie blockierend: Fehler loggen, Gesuch trotzdem speichern.
- Ton der Website: „ihr/euch“ (wie auf dem Plakat). E-Mails an
  Erziehungsberechtigte: „Sie“.
- `ContentSeeder` legt nur fehlende Startinhalte an und überschreibt nie
  Bearbeitetes – er läuft bei jedem Deploy.
- Rollen (`users.roles`, mehrere möglich): `admin` = Webadmin (Benutzer,
  Einstellungen, Inhalte), `redaktion` = Inhalte, `mitglieder` = Backoffice
  Mitglieder, `reservationen` = Backoffice Reservationen.
  Zugriff je Bereich: `User::PANEL_ROLES`; jede Backoffice-Resource/-Widget prüft
  zusätzlich ihre Rolle (`canManageMembers()` / `canManageReservations()`).
  Webadmin sieht ohne `mitglieder` keine Personendaten – so lassen.
- Raumreservation: Pakete in `App\Support\RoomPackage`, Regeln in
  `App\Support\ReservationRules`, Mails über `App\Support\ReservationMails`.
  Nur Status `bestaetigt` belegt einen Raum.
- Produktiv-DB ist **MySQL 5.7**: keine JSON-Spalten mit Default, keine
  Funktions-Defaults in Migrationen.
- Datum/Zeit aus `attributesToArray()` kommen als UTC-ISO – vor dem Anzeigen in
  `config('app.timezone')` umrechnen.
- Backoffice-Code unter `app/Filament/Backoffice/`. Mitgliedschaft entsteht nur
  über „Aufnehmen“ (`MembershipApplication::accept()`) oder direkte Erfassung.

## Vor jedem Commit
```bash
php artisan test
npm run build
```
