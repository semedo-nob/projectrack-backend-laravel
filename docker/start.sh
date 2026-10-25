#!/usr/bin/env bash
# Boot Laravel on Render: map env, migrate, then serve.
set -euo pipefail

cd /var/www/html

mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

# Render Postgres provides DATABASE_URL; Laravel 13 reads DB_URL.
if [[ -z "${DB_URL:-}" && -n "${DATABASE_URL:-}" ]]; then
  export DB_URL="$DATABASE_URL"
fi

# Public HTTPS URL for share links (/p/{token}) and redirects.
if [[ -z "${APP_URL:-}" && -n "${RENDER_EXTERNAL_URL:-}" ]]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

if [[ -z "${APP_KEY:-}" ]]; then
  echo "APP_KEY is not set. Add it in the Render dashboard (php artisan key:generate --show)."
  exit 1
fi

if [[ "$APP_KEY" != base64:* ]]; then
  export APP_KEY="base64:${APP_KEY}"
fi

php artisan storage:link >/dev/null 2>&1 || true

echo "==> Migrating database…"
php artisan migrate --force

echo "==> Caching config / routes / views…"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Nginx on the same host is the only public listener. Do not bind 0.0.0.0.
BIND="${APP_BIND:-127.0.0.1}"
PORT="${PORT:-8000}"
echo "==> Serving ProjectRack on ${BIND}:${PORT} (APP_URL=${APP_URL:-unset})"
exec php artisan serve --host="$BIND" --port="$PORT" --no-reload
