#!/bin/sh
set -e

mkdir -p /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache
chown -R www-data:www-data /app/storage /app/bootstrap/cache

cd /app
php artisan optimize:clear
php artisan package:discover --ansi
php artisan view:cache || true

exec su-exec www-data frankenphp run --config /etc/frankenphp/Caddyfile
