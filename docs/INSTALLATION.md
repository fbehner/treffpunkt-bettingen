# Installation auf green.ch (Paket S)

Einmalige Einrichtung des Servers. Danach genügt für jede neue Version
`scripts/deploy.sh`.

## Voraussetzungen prüfen

Im Kundenportal von green.ch bzw. per SSH:

| Was | Wert | Wie prüfen |
| --- | --- | --- |
| PHP-Version (Web **und** SSH) | mindestens 8.2 | `php -v` per SSH, PHP-Einstellung im Portal |
| PHP-Erweiterungen | `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `zip`, `gd` | `php -m` |
| MySQL-Datenbank | eine leere Datenbank + Benutzer | Kundenportal → Datenbanken |
| SSH-Zugang | aktiv, idealerweise mit SSH-Schlüssel | Kundenportal |
| Webroot | muss auf `…/public` zeigen | siehe unten |

Falls `php` per SSH eine ältere Version ist, gibt es auf dem Server oft
Befehle wie `php8.3` – dann `DEPLOY_PHP=php8.3` setzen.

## 1. Ordner festlegen

Die Anwendung liegt **ausserhalb** des öffentlichen Webordners, nur `public/`
ist von aussen erreichbar. Beispiel:

```
/home/USER/app            ← Anwendung (DEPLOY_PATH)
/home/USER/app/public     ← muss der Webroot von treffpunkt-bettingen.ch sein
```

**Webroot umstellen:** Im Kundenportal bei der Domain den Dokumentenstamm auf
`app/public` setzen.

**Falls das nicht geht:** Den bestehenden Webordner (z. B. `httpdocs`) durch
einen symbolischen Link ersetzen:

```bash
mv ~/httpdocs ~/httpdocs.alt && ln -s ~/app/public ~/httpdocs
```

## 2. Zugang auf dem eigenen Rechner einrichten

Im Projektordner eine Datei `.deploy.env` anlegen (wird nicht ins Repository
übernommen):

```bash
DEPLOY_HOST=[SSH-HOST]
DEPLOY_USER=[SSH-BENUTZER]
DEPLOY_PATH=/home/[SSH-BENUTZER]/app
DEPLOY_PHP=php
```

## 3. Erste Übertragung

```bash
scripts/deploy.sh --dry-run   # zeigt, was übertragen würde
```

Dann auf dem Server die Konfiguration anlegen:

```bash
ssh USER@HOST
cd ~/app
cp .env.production.example .env
nano .env                     # Datenbank- und Mail-Zugang eintragen
php artisan key:generate
chmod -R u+rwX storage bootstrap/cache
```

Danach lokal:

```bash
scripts/deploy.sh
```

## 4. Erstes Admin-Konto

```bash
ssh USER@HOST
cd ~/app && php artisan verein:admin
```

Die Verwaltung ist unter **https://treffpunkt-bettingen.ch/admin** erreichbar.
Weitere Personen legt ein Admin dort unter *Verwaltung → Benutzer* an.

## 5. Cronjob (einer von 4 im Paket S)

Für geplante Aufgaben (z. B. später Erinnerungen bei Raumreservierungen):

```
* * * * *  cd /home/USER/app && php artisan schedule:run >> /dev/null 2>&1
```

Falls green.ch nur seltenere Intervalle erlaubt, z. B. alle 5 Minuten – das
reicht für einen Verein.

## Sicherung

Die Inhalte liegen in der MySQL-Datenbank. Regelmässig über das Kundenportal
(phpMyAdmin → Export) sichern – oder per Cronjob mit `mysqldump`.

## Neue Version von Hand einspielen (ZIP-Paket)

Wenn `scripts/deploy.sh` nicht genutzt wird, sondern ein fertiges Paket
`treffpunkt-bettingen-upload.zip` per SFTP ins Home-Verzeichnis geladen wurde:

```bash
cd ~
mv app app-alt                                   # alte Version aufheben
unzip -q treffpunkt-bettingen-upload.zip         # neue Version nach ~/app
cp app-alt/.env app/                             # Konfiguration übernehmen
cp -a app-alt/storage/. app/storage/             # Uploads, Logs übernehmen
cd app
chmod -R u+rwX storage bootstrap/cache
php artisan migrate --force
php artisan db:seed --class=ContentSeeder --force
php artisan storage:link
php artisan optimize:clear && php artisan optimize && php artisan filament:optimize
```

Läuft alles, kann `~/app-alt` gelöscht werden. Falls etwas schiefgeht:
`mv ~/app ~/app-neu && mv ~/app-alt ~/app` stellt die alte Version wieder her
(die Datenbank-Änderungen sind abwärtskompatibel).

## E-Mail-Versand

Beitrittsgesuche verschicken E-Mails (Bestätigung, Meldung an den Vorstand,
Bestätigungslink für Erziehungsberechtigte). Dafür in der `.env` das Postfach
`info@treffpunkt-bettingen.ch` von green.ch eintragen (`MAIL_HOST`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, siehe `.env.production.example`) und danach
`php artisan optimize` ausführen. Ohne funktionierenden Versand werden Gesuche
trotzdem gespeichert; der Fehler steht in `storage/logs/`.
