# syntax=docker/dockerfile:1

# FrankenPHP bundles PHP 8.4 and a Caddy web server in a single process — no
# nginx, no php-fpm. supervisord is PID 1 so this ONE container can also run
# a queue worker and scheduler if the app ever needs them (it doesn't today —
# see docker/supervisord.conf and DEPLOY.md "Process model").
#
# This repo deploys as a single Dokploy Application (one container, one
# webhook) — see .github/workflows/release.yml and DEPLOY.md.

# ---------------------------------------------------------------------------
# base — the PHP runtime every other stage builds on, so vendor resolves the
# exact platform requirements production runs with.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine AS base

WORKDIR /app

# bash: Alpine ships only busybox ash. bash makes `docker exec` sessions and
# one-off scripts behave as expected (ash's missing brace expansion etc. is a
# real class of surprise worth removing for a few KB).
RUN apk add --no-cache bash

# Extensions this app actually uses, and nothing else:
#   - pdo_mysql: DB_CONNECTION=mysql (config/database.php / .env.example)
#   - opcache:   production performance (see docker/php.ini)
# Audited against app/ and composer.lock's platform requirements — no
# bcmath/intl/gd/zip/redis/pcntl usage anywhere in this app, so none are
# installed here. Re-audit if a future dependency needs one.
RUN install-php-extensions \
        opcache \
        pdo_mysql

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# ---------------------------------------------------------------------------
# vendor — composer install keyed on the lockfile alone, so the slow
# network-bound layer survives code-only changes.
# ---------------------------------------------------------------------------
FROM base AS vendor

RUN install-php-extensions @composer

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev --no-scripts --no-autoloader \
        --prefer-dist --no-interaction --no-progress

# Autoloader generation needs the application classes present. routes/ is
# here too, not because dump-autoload needs it, but because booting the
# console kernel at all (for package:discover, below) eagerly registers
# bootstrap/app.php's withRouting() — including routes/web.php — regardless
# of HTTP vs console context.
COPY app ./app
COPY bootstrap ./bootstrap
COPY database ./database
COPY routes ./routes
COPY artisan ./artisan

RUN composer dump-autoload --no-dev --optimize --no-scripts --no-interaction

# Regenerates bootstrap/cache/packages.php and services.php against this
# --no-dev vendor/. Without this, whatever a contributor's local machine last
# wrote to bootstrap/cache/ (almost certainly WITH dev packages like
# laravel/pail present) gets baked into the image by the runtime stage's own
# COPY . . below — and Laravel then tries to register a provider class
# (e.g. Laravel\Pail\PailServiceProvider) that doesn't exist in --no-dev
# vendor/, crashing every request. The runtime stage explicitly copies this
# corrected bootstrap/cache/ from here, overwriting that stale copy.
#
# Booting the framework this far needs storage/framework/views to already
# exist — Blade's compiler validates the path eagerly and throws "Please
# provide a valid cache path" otherwise. This is throwaway (the runtime
# stage creates the real storage/ tree itself); it only has to exist for
# this one command.
RUN mkdir -p bootstrap/cache storage/framework/views && php artisan package:discover --ansi

# ---------------------------------------------------------------------------
# assets — Vite/Tailwind build.
#
# vendor/ is copied in because Tailwind v4's @source directives in
# resources/css/app.css point INTO it: the Majid DS kit's own Blade views
# (vendor/mahdimajidzadeh/ds/resources/views) and the framework's pagination
# views. Without vendor/ present, those utility classes are never generated
# and Flux/Majid DS components silently lose their styling.
#
# The build also reaches the network: vite.config.js's bunny() font provider
# fetches Instrument Sans from fonts.bunny.net during `npm run build`. That is
# WHY the image is built in CI (which has outbound network) rather than on
# the Dokploy host — a server-side build would need that same egress.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

# ---------------------------------------------------------------------------
# runtime — the published image. Code is root-owned and read-only to the
# runtime user; only storage/ and bootstrap/cache are writable.
# ---------------------------------------------------------------------------
FROM base AS runtime

# supervisord is PID 1. Today it runs only the `web` program (no queue, no
# scheduler — see docker/supervisord.conf for why), but every repo in this
# family runs the same process model so a future queue/schedule addition is
# a supervisord program, not a redesign.
RUN apk add --no-cache supervisor

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_MIGRATIONS=true

COPY . .
COPY --from=vendor /app/vendor ./vendor
# Overwrites whatever COPY . . just brought in from bootstrap/cache/ with the
# --no-dev-correct version generated in the vendor stage — see the
# package:discover comment there.
COPY --from=vendor /app/bootstrap/cache ./bootstrap/cache
COPY --from=assets /app/public/build ./public/build

COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /data/caddy /config/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

# storage:link at build time (runtime public/ is read-only). Relative target so
# it still resolves when /app/storage is a mounted volume.
RUN ln -sfn ../storage/app/public public/storage

USER www-data

EXPOSE 8080

# Deliberately does not touch the database: a DB blip must not become a
# container restart loop.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
