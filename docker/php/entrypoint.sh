#!/usr/bin/env sh
set -e

# Ensure writable dirs are owned by www-data (FPM pool user).
# composer create-project + bootstrap.sh ran as root, leaving these root-owned.
if [ -d /var/www/html/storage ]; then
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
  chmod -R u+rwX,g+rwX /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

# Wait for Postgres before booting Laravel (max 30 attempts, 1s each)
if [ -n "${DB_HOST:-}" ]; then
  for i in $(seq 1 30); do
    if pg_isready -h "${DB_HOST}" -U "${DB_USERNAME:-webfactory}" -q; then
      break
    fi
    echo "Waiting for ${DB_HOST}:${DB_PORT:-5432}... ($i/30)"
    sleep 1
  done
fi

# Generate APP_KEY if missing (idempotent)
if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force --no-interaction || true
fi

# Storage symlink (idempotent)
[ -L public/storage ] || php artisan storage:link --no-interaction || true

# Run pending migrations on first boot of wf-app only (skip for horizon/scheduler/reverb)
if [ "${ENTRYPOINT_RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force --no-interaction || true
fi

exec "$@"
