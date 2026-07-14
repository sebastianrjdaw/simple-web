#!/bin/sh
set -eu

if [ "${SIMPLEVIEW_SKIP_BOOTSTRAP:-false}" = "true" ]; then
    exec "$@"
fi

for directory in database media thumbnails backups logs cache; do
    mkdir -p "/data/$directory"
done
touch /data/database/database.sqlite
php artisan filament:assets
php artisan config:cache
php artisan view:cache
php artisan migrate --force
php artisan simpleview:create-admin
exec "$@"
