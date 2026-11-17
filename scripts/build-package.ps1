# Baut das Upload-Paket fuer green.ch (Windows, ohne rsync).
#
# Nimmt den committeten Stand (HEAD) - nicht die Arbeitskopie -, installiert
# die Abhaengigkeiten ohne Entwicklungswerkzeuge, baut CSS/JS und packt alles
# als ZIP mit dem Ordner app/. Die lokale Entwicklungsumgebung bleibt unberuehrt.
#
# Aufruf:  powershell -ExecutionPolicy Bypass -File scripts\build-package.ps1
# Ergebnis (neben dem Repo-Ordner):
#   treffpunkt-bettingen-upload.zip   Anwendung
#   server-update.sh                  spielt das Paket auf dem Server ein

param(
    [string]$OutDir = (Join-Path $PSScriptRoot '..\..')
)

$ErrorActionPreference = 'Stop'
$env:Path = [Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [Environment]::GetEnvironmentVariable('Path', 'User')

function Run([string]$exe, [string[]]$arguments) {
    & $exe @arguments
    if ($LASTEXITCODE -ne 0) { throw "$exe $($arguments -join ' ') ist fehlgeschlagen (Code $LASTEXITCODE)." }
}

$repo = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$OutDir = (Resolve-Path $OutDir).Path
Set-Location $repo

$dirty = git status --porcelain
if ($dirty) { Write-Warning 'Es gibt nicht committete Aenderungen - sie kommen NICHT ins Paket.' }
$commit = (git rev-parse --short HEAD).Trim()
$branch = (git rev-parse --abbrev-ref HEAD).Trim()
Write-Host "-> Paket aus $branch @ $commit"

$work = Join-Path $env:TEMP "treffpunkt-build-$commit"
if (Test-Path $work) { Remove-Item $work -Recurse -Force }
$app = Join-Path $work 'app'
New-Item -ItemType Directory -Force $app | Out-Null

Write-Host '-> Committeten Stand auspacken'
$src = Join-Path $work 'src.zip'
Run git @('archive', '--format=zip', '-o', $src, 'HEAD')
Run tar @('-xf', $src, '-C', $app)
Remove-Item $src

Set-Location $app
Write-Host '-> PHP-Abhaengigkeiten (ohne dev)'
Run composer @('install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction')
Write-Host '-> CSS/JS bauen'
Run npm @('ci', '--no-audit', '--no-fund')
Run npm @('run', 'build')

# Was auf dem Server nicht gebraucht wird
foreach ($d in 'node_modules', 'tests', '.github') {
    if (Test-Path $d) { Remove-Item $d -Recurse -Force }
}
Remove-Item 'database\database.sqlite' -ErrorAction SilentlyContinue
Set-Content -Path 'REVISION' -Value "$commit $(Get-Date -Format 'yyyy-MM-dd HH:mm')" -Encoding ascii

$check = Select-String -Path 'vendor\composer\platform_check.php' -Pattern 'PHP_VERSION_ID >= (\d+)' | Select-Object -First 1
if ($check) {
    $id = [int]$check.Matches[0].Groups[1].Value
    Write-Host ("-> Benoetigt auf dem Server mindestens PHP {0}.{1}" -f [math]::Floor($id / 10000), [math]::Floor(($id % 10000) / 100))
}

Write-Host '-> ZIP packen'
Set-Location $work
$zip = Join-Path $OutDir 'treffpunkt-bettingen-upload.zip'
if (Test-Path $zip) { Remove-Item $zip -Force }
Run tar @('-a', '-c', '-f', $zip, 'app')
Copy-Item (Join-Path $repo 'scripts\server-update.sh') (Join-Path $OutDir 'server-update.sh') -Force

Set-Location $repo
Remove-Item $work -Recurse -Force

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host ''
Write-Host "Fertig ($size MB, Stand $commit):"
Write-Host "  $zip"
Write-Host "  $(Join-Path $OutDir 'server-update.sh')"
Write-Host 'Beide per SFTP ins Home-Verzeichnis laden, dann per SSH:  bash ~/server-update.sh'
