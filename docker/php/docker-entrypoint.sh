#!/bin/sh
set -e

# Only attempt when Laravel app exists
if [ -f /var/www/html/artisan ]; then
    mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
fi

exec "$@"
