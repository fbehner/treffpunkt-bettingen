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
- `ContentSeeder` legt nur fehlende Startinhalte an und überschreibt nie
  Bearbeitetes – er läuft bei jedem Deploy.
- Rollen: `admin` (alles), `redaktion` (Inhalte). Benutzerverwaltung nur admin.

## Vor jedem Commit
```bash
php artisan test
npm run build
```
