#!/bin/sh
set -e

cd /app
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

DATABASE_URL="${DATABASE_URL_UNPOOLED:-$DATABASE_URL}" php artisan migrate --force || true
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\Schema::hasTable("users") && App\Models\User::count() === 0) {
    Illuminate\Support\Facades\Artisan::call("db:seed", ["--force" => true]);
}
' || true

exec frankenphp run --config /etc/frankenphp/Caddyfile
