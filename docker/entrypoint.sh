#!/bin/sh
set -eu

cd /var/www/html

echo "Container starting..."

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ -f artisan ]; then
  php artisan optimize:clear >/dev/null 2>&1 || true
fi

if [ "${WAIT_FOR_DB:-true}" = "true" ] && [ -n "${DB_HOST:-}" ] && [ -n "${DB_PORT:-}" ]; then
  echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
  ATTEMPTS="${DB_WAIT_ATTEMPTS:-30}"
  i=1
  while [ "$i" -le "$ATTEMPTS" ]; do
    if nc -z "$DB_HOST" "$DB_PORT" >/dev/null 2>&1; then
      echo "Database port is reachable."
      break
    fi
    if [ "$i" -eq "$ATTEMPTS" ]; then
      echo "Database did not become reachable in time."
      exit 1
    fi
    sleep 2
    i=$((i + 1))
  done
fi

if [ "${RUN_MIGRATIONS_ON_STARTUP:-true}" = "true" ] && [ -f artisan ]; then
  echo "Running database migrations..."
  php artisan migrate --force
fi

if [ "${CACHE_CONFIG_ON_STARTUP:-false}" = "true" ] && [ -f artisan ]; then
  echo "Caching Laravel config and routes..."
  php artisan config:cache
  php artisan route:cache
fi

exec "$@"
