FROM dunglas/frankenphp:1-php8.3-bookworm AS base

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions intl zip pdo_pgsql pgsql

WORKDIR /app

FROM base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
        --prefer-dist

COPY . .
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
    && composer dump-autoload --no-dev --no-scripts --classmap-authoritative

FROM base AS runtime

COPY --from=dependencies --chown=www-data:www-data /app /app
COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile

RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync

USER www-data

CMD ["sh", "-c", "cd /app && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php && php artisan package:discover --ansi && exec frankenphp run --config /etc/frankenphp/Caddyfile"]
