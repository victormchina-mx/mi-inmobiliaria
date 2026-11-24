#!/usr/bin/env bash
# Actualiza TARZO en Hostinger compartido. Ejecutar por SSH desde la carpeta de la app.
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$(dirname "$0")/../.."

echo "→ Modo mantenimiento"
$PHP_BIN artisan down --retry=60 || true
trap '$PHP_BIN artisan up' EXIT

echo "→ Descargando cambios"
git pull --ff-only

echo "→ Dependencias"
# --no-scripts: Hostinger desactiva proc_open y los scripts de Composer fallan; se corren a mano.
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --no-scripts
$PHP_BIN artisan package:discover --ansi
$PHP_BIN artisan filament:upgrade

echo "→ Base de datos"
$PHP_BIN artisan migrate --force
$PHP_BIN artisan db:seed --force          # roles, permisos y catálogos (idempotente)

echo "→ Cachés"
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan optimize
$PHP_BIN artisan filament:optimize

echo "→ Reiniciar trabajos en cola (el cron los vuelve a lanzar)"
$PHP_BIN artisan queue:restart

echo "✓ Actualización terminada"
