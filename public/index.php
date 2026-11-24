<?php

$bootstrapCache = __DIR__.'/../bootstrap/cache';
foreach (['packages.php', 'services.php', 'config.php', 'routes.php', 'events.php'] as $cacheFile) {
    $cachePath = $bootstrapCache.'/'.$cacheFile;
    if (is_file($cachePath)) {
        @unlink($cachePath);
    }
}

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
