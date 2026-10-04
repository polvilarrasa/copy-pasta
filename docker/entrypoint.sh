#!/bin/sh
# Waits for Postgres, applies migrations when this container is the migrator, caches the framework
# and then hands over to the command (FrankenPHP for the web app, a worker or the scheduler otherwise).
set -e

attempts=0
until php -r '
    $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE"));
    try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }
'; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 60 ]; then
        echo "Postgres did not become ready in time." >&2
        exit 1
    fi
    sleep 2
done

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan optimize --no-interaction

exec "$@"
