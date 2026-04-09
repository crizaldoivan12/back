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
  DB_REACHABLE="false"
  i=1
  while [ "$i" -le "$ATTEMPTS" ]; do
    if nc -z "$DB_HOST" "$DB_PORT" >/dev/null 2>&1; then
      echo "Database port is reachable."
      DB_REACHABLE="true"
      break
    fi
    echo "Database not reachable yet (attempt ${i}/${ATTEMPTS})."
    if [ "$i" -eq "$ATTEMPTS" ]; then
      echo "Database did not become reachable in time."
      if [ "${DB_WAIT_STRICT:-false}" = "true" ]; then
        exit 1
      fi
      echo "Continuing startup without confirmed database connectivity."
      break
    fi
    sleep 2
    i=$((i + 1))
  done
else
  DB_REACHABLE="unknown"
fi

if [ "${RUN_MIGRATIONS_ON_STARTUP:-true}" = "true" ] && [ -f artisan ]; then
  if [ "${DB_REACHABLE:-unknown}" = "false" ]; then
    echo "Skipping migrations because the database is not reachable."
  else
    echo "Running database migrations..."
    php artisan migrate --force
  fi
fi

if [ "${RUN_SEEDERS_ON_STARTUP:-true}" = "true" ] && [ -f artisan ]; then
  if [ "${DB_REACHABLE:-unknown}" = "false" ]; then
    echo "Skipping seeders because the database is not reachable."
  else
    echo "Running database seeders..."
    php artisan db:seed --force
  fi
fi

if [ "${CACHE_CONFIG_ON_STARTUP:-false}" = "true" ] && [ -f artisan ]; then
  echo "Caching Laravel config and routes..."
  php artisan config:cache
  php artisan route:cache
fi

exec "$@"
