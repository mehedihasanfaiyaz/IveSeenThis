#!/bin/sh
set -eu

cd /var/www/html

if [ ! -s .env ] || ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan migrate --force --no-interaction
php artisan storage:link --force 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
