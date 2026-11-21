#!/usr/bin/env bash
# Despliegue en VPS con versiones (releases) y cambio atómico de enlace "current".
# Estructura: /var/www/tarzo/{releases,shared,current}
set -euo pipefail

BASE=/var/www/tarzo
REPO="${REPO:-https://github.com/victormchina-mx/REPOSITORIO.git}"
SUBDIR="${SUBDIR:-taller-tarzo}"
RAMA="${RAMA:-main}"
RELEASE="$BASE/releases/$(date +%Y%m%d%H%M%S)"

mkdir -p "$BASE/releases" "$BASE/shared/storage"
git clone --depth 1 --branch "$RAMA" "$REPO" "$RELEASE.src"
mv "$RELEASE.src/$SUBDIR" "$RELEASE" && rm -rf "$RELEASE.src"

cd "$RELEASE"
rm -rf storage && ln -s "$BASE/shared/storage" storage
ln -s "$BASE/shared/.env" .env

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
php artisan filament:optimize

ln -sfn "$RELEASE" "$BASE/current"
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate || true
php artisan reverb:restart || true

# Conservar las últimas 5 versiones
ls -1dt "$BASE"/releases/* | tail -n +6 | xargs -r rm -rf
echo "✓ Desplegado $RELEASE"
