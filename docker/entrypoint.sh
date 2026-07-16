#!/bin/sh
set -e

cd /var/www

mkdir -p storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Ensure public disk files are web-accessible (products, articles, logos, etc.)
php artisan storage:link --force 2>/dev/null || php artisan storage:link || true

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
