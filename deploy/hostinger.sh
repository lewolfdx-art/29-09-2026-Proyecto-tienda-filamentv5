#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$APP_DIR"

if [ ! -f .env ]; then
    echo "Falta el archivo .env en $APP_DIR" >&2
    exit 1
fi

echo "==> Activando modo mantenimiento"
"$PHP_BIN" artisan down --retry=60 || true

trap 'echo "El despliegue falló. La aplicación sigue en mantenimiento." >&2; exit 1' ERR

echo "==> Actualizando código"
git pull --ff-only

echo "==> Instalando dependencias PHP"
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Ejecutando migraciones"
"$PHP_BIN" artisan migrate --force

echo "==> Sincronizando permisos del panel"
"$PHP_BIN" artisan app:sync-permissions

echo "==> Compilando assets"
if command -v npm >/dev/null 2>&1; then
    npm ci
    npm run build
else
    echo "npm no está disponible; se usará public/build subido desde local."
fi

echo "==> Enlaces y cachés"
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

echo "==> Verificando configuración de producción"
"$PHP_BIN" artisan app:production-check

trap - ERR

echo "==> Desactivando modo mantenimiento"
"$PHP_BIN" artisan up

echo "==> Despliegue completado"
