#!/bin/sh
set -e

# Build Laravel caches at container START, not at image build time. All
# runtime config (DB_*, PTABLE_ADMIN_*, APP_KEY, …) comes from the Dokploy
# Application's Environment tab and is injected into the container's process
# environment — nothing is baked into the image, so config:cache must run
# against the environment this specific container actually has, every boot.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
