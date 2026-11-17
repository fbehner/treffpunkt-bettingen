#!/usr/bin/env bash
#
# Spielt treffpunkt-bettingen-upload.zip auf dem Server ein (green.ch, Paket S).
# Erzeugt von scripts/build-package.ps1. Beide Dateien liegen im Home-Verzeichnis.
#
# Ablauf: Datenbank sichern -> Wartungsmodus -> neue Version auspacken,
# .env und storage/ übernehmen -> alte Version nach ~/app-alt-DATUM ->
# migrieren, Caches neu -> online.
#
# Aufruf:  bash ~/server-update.sh
#          DEPLOY_PHP=php8.3 bash ~/server-update.sh   # falls nötig
#          SKIP_BACKUP=1 bash ~/server-update.sh       # ohne DB-Sicherung

set -euo pipefail

cd ~
PHP="${DEPLOY_PHP:-php}"
ZIP="treffpunkt-bettingen-upload.zip"
STAMP="$(date +%Y%m%d-%H%M)"
NEW="app-neu-${STAMP}"
OLD="app-alt-${STAMP}"

fail() { echo "✗ $*" >&2; exit 1; }

[[ -f "$ZIP" ]]     || fail "$ZIP fehlt im Home-Verzeichnis."
[[ -f app/.env ]]   || fail "~/app/.env fehlt – ist die Anwendung schon eingerichtet?"
command -v unzip >/dev/null || fail "unzip ist nicht installiert."

echo "→ Neue Version auspacken"
rm -rf "$NEW"
mkdir "$NEW"
unzip -q "$ZIP" -d "$NEW"
[[ -f "$NEW/app/artisan" ]] || fail "Das ZIP enthält keinen Ordner app/."
echo "  Stand: $(cat "$NEW/app/REVISION" 2>/dev/null || echo unbekannt)"

# Composer prüft beim Start die PHP-Version, die das Paket braucht.
"$PHP" "$NEW/app/artisan" --version >/dev/null \
    || { rm -rf "$NEW"; fail "Die neue Version läuft nicht mit $("$PHP" -r 'echo PHP_VERSION;') (siehe Meldung oben). DEPLOY_PHP setzen, z. B. php8.3."; }

if [[ -z "${SKIP_BACKUP:-}" ]]; then
    if command -v mysqldump >/dev/null; then
        echo "→ Datenbank sichern"
        env_val() { grep -E "^$1=" app/.env | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"; }
        mkdir -p ~/backup
        DUMP=~/backup/db-${STAMP}.sql.gz
        MYSQL_PWD="$(env_val DB_PASSWORD)" mysqldump --single-transaction --no-tablespaces \
            -h "$(env_val DB_HOST)" -P "$(env_val DB_PORT)" -u "$(env_val DB_USERNAME)" \
            "$(env_val DB_DATABASE)" | gzip > "$DUMP" \
            || fail "Sicherung fehlgeschlagen – abgebrochen, nichts verändert. (SKIP_BACKUP=1 überspringt sie.)"
        echo "  $DUMP ($(du -h "$DUMP" | cut -f1))"
    else
        echo "! mysqldump nicht gefunden – bitte vorher in phpMyAdmin exportieren."
        read -r -p "  Trotzdem weiter? [j/N] " ok
        [[ "$ok" == "j" ]] || fail "Abgebrochen, nichts verändert."
    fi
fi

echo "→ Wartungsmodus"
(cd app && "$PHP" artisan down --retry=30) || true

trap 'echo; echo "✗ Fehler. Zurück zur alten Version:"; echo "  cd ~ && mv app '"$NEW"'-defekt && mv '"$OLD"' app && cd app && '"$PHP"' artisan up"' ERR

echo "→ Konfiguration und Dateien übernehmen"
cp app/.env "$NEW/app/.env"
cp -a app/storage/. "$NEW/app/storage/"

echo "→ Versionen tauschen"
mv app "$OLD"
mv "$NEW/app" app
rmdir "$NEW"

cd app
chmod -R u+rwX storage bootstrap/cache
"$PHP" artisan migrate --force
"$PHP" artisan db:seed --class=ContentSeeder --force
"$PHP" artisan storage:link 2>/dev/null || true
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize
"$PHP" artisan up
trap - ERR

echo
echo "✓ Online: $(cat REVISION)"
echo "  Alte Version: ~/$OLD (löschen, wenn alles läuft)"
echo "  Zurück:  cd ~ && mv app app-defekt && mv $OLD app"
