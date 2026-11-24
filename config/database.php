<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', (env('DATABASE_URL') || env('POSTGRES_URL')) ? 'pgsql' : 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL', env('POSTGRES_URL')),
            'host' => env('DB_HOST', env('POSTGRES_HOST', '127.0.0.1')),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', env('POSTGRES_DATABASE', 'postgres')),
            'username' => env('DB_USERNAME', env('POSTGRES_USER', 'postgres')),
            'password' => env('DB_PASSWORD', env('POSTGRES_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => env('DB_SCHEMA', 'public'),
            'search_path' => env('DB_SCHEMA', 'public'),
            'sslmode' => env('DB_SSLMODE', 'require'),
            'options' => extension_loaded('pdo_pgsql') ? [
                PDO::ATTR_EMULATE_PREPARES => true,
            ] : [],
        ],
    ],

    'migrations' => 'migrations',
];
