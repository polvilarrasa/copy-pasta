#!/bin/sh
# Waits for Postgres, caches the framework and then hands over to the command (FrankenPHP for the web app,
# a worker or the scheduler otherwise). Migrations are a separate deploy step, never run from here.
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

php artisan optimize --no-interaction

exec "$@"
