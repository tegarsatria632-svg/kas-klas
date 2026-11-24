#!/bin/sh
set -e

cd /app
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

(
  DATABASE_URL="${DATABASE_URL_UNPOOLED:-$DATABASE_URL}" php -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    try {
      $locked = true;
      if (config("database.default") === "pgsql") {
        $row = Illuminate\Support\Facades\DB::selectOne("SELECT pg_try_advisory_lock(6624600021) AS locked");
        $locked = !empty($row->locked);
      }
      if ($locked) {
        Illuminate\Support\Facades\Artisan::call("migrate", ["--force" => true]);
        if (Illuminate\Support\Facades\Schema::hasTable("users") && App\Models\User::count() === 0) {
          Illuminate\Support\Facades\Artisan::call("db:seed", ["--force" => true]);
        }
        if (config("database.default") === "pgsql") {
          Illuminate\Support\Facades\DB::select("SELECT pg_advisory_unlock(6624600021)");
        }
      }
    } catch (\Throwable $e) {
      error_log("Startup migrate/seed note: " . $e->getMessage());
    }
  '
) &

exec frankenphp run --config /etc/frankenphp/Caddyfile
