#!/usr/bin/env bash
#
# Veröffentlicht die Website auf dem Webhosting (green.ch, Paket S) per SSH.
#
# Das Paket S hat kein Git und keinen Node.js auf dem Server. Deshalb wird
# hier lokal (oder in GitHub Actions) alles fertig gebaut und dann per rsync
# hochgeladen.
#
# Benötigte Umgebungsvariablen (z. B. in einer Datei .deploy.env, nicht im Repo):
#   DEPLOY_HOST     SSH-Host, z. B. sshXX.green.ch
#   DEPLOY_USER     SSH-Benutzer
#   DEPLOY_PATH     Ordner für die Anwendung auf dem Server, z. B. /home/USER/app
#   DEPLOY_PHP      PHP-Befehl auf dem Server (Standard: php)
#   DEPLOY_PORT     SSH-Port (Standard: 22)
#
# Aufruf:  scripts/deploy.sh            # bauen, hochladen, migrieren
#          scripts/deploy.sh --dry-run  # nur zeigen, was übertragen würde

set -euo pipefail

cd "$(dirname "$0")/.."

[[ -f .deploy.env ]] && source .deploy.env

: "${DEPLOY_HOST:?DEPLOY_HOST fehlt}"
: "${DEPLOY_USER:?DEPLOY_USER fehlt}"
: "${DEPLOY_PATH:?DEPLOY_PATH fehlt}"
DEPLOY_PHP="${DEPLOY_PHP:-php}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"

DRY=""
[[ "${1:-}" == "--dry-run" ]] && DRY="--dry-run"

SSH="ssh -p ${DEPLOY_PORT}"
REMOTE="${DEPLOY_USER}@${DEPLOY_HOST}"

echo "→ Abhängigkeiten installieren (ohne Entwicklungswerkzeuge)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

echo "→ Dateien übertragen nach ${REMOTE}:${DEPLOY_PATH}"
rsync -az --delete ${DRY} -e "${SSH}" \
    --exclude='.git/' \
    --exclude='.github/' \
    --exclude='node_modules/' \
    --exclude='tests/' \
    --exclude='.env' \
    --exclude='.deploy.env' \
    --exclude='storage/' \
    --exclude='database/database.sqlite' \
    --exclude='public/storage' \
    ./ "${REMOTE}:${DEPLOY_PATH}/"

# storage/ nur anlegen, nie überschreiben (Logs, Sessions, Uploads).
rsync -az ${DRY} -e "${SSH}" --ignore-existing \
    --include='*/' --include='.gitignore' --exclude='*' \
    storage/ "${REMOTE}:${DEPLOY_PATH}/storage/"

if [[ -n "$DRY" ]]; then
    echo "Probelauf beendet – nichts verändert."
    exit 0
fi

echo "→ Auf dem Server: Wartungsmodus, Datenbank, Caches"
${SSH} "${REMOTE}" bash -s <<EOF
set -euo pipefail
cd "${DEPLOY_PATH}"
${DEPLOY_PHP} artisan down --retry=30 || true
${DEPLOY_PHP} artisan migrate --force
${DEPLOY_PHP} artisan db:seed --class=ContentSeeder --force
${DEPLOY_PHP} artisan optimize:clear
${DEPLOY_PHP} artisan optimize
${DEPLOY_PHP} artisan filament:optimize
${DEPLOY_PHP} artisan storage:link || true
${DEPLOY_PHP} artisan up
EOF

echo "→ Lokale Entwicklungsabhängigkeiten wiederherstellen"
composer install --no-interaction --quiet

echo "✓ Veröffentlicht."
