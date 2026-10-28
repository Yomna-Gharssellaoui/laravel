#!/bin/sh
set -e

cd /var/www/html

# SQLite: create the database file on first start
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite
fi

# Only the web container migrates (the queue and scheduler containers set RUN_MIGRATIONS=false)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
    # Demo data only once (the marker lives in the persistent storage volume)
    if [ "${SEED_DATABASE:-false}" = "true" ] && [ ! -f storage/app/.seeded ]; then
        php artisan db:seed --force
        touch storage/app/.seeded
    fi
fi

mkdir -p storage/app/public
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
