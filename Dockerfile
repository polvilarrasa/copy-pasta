# syntax=docker/dockerfile:1

# PHP base with the extensions the app needs and Composer for the dependency stage.
FROM dunglas/frankenphp:1-php8.4-bookworm AS base
RUN install-php-extensions pdo_pgsql intl pcntl opcache zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# PHP dependencies without development packages. Stylesheets import Flux from vendor/, so assets need it too.
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# Frontend assets, built once and copied into the final image.
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY app ./app
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM base AS final
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_LOCALE=es \
    LOG_CHANNEL=stderr \
    SERVER_NAME=:8080
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1
ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
