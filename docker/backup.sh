#!/bin/sh
# Daily custom-format dump of the application database, keeping the last 14 days on the backups volume.
set -eu

mkdir -p /backups
while true; do
    stamp=$(date +%Y%m%d-%H%M%S)
    pg_dump --format=custom --file "/backups/copypastas-${stamp}.dump" "$PGDATABASE"
    find /backups -name 'copypastas-*.dump' -mtime +14 -delete
    sleep 86400
done
