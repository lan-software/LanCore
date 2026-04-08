#syntax=docker/dockerfile:1.7

# =============================================================================
# LanCore — Production Docker image
# =============================================================================
#
# Three-stage build:
#   1. deps      — composer install + Wayfinder TypeScript generation
#   2. frontend  — Vite asset build (Node 22)
#   3. production — FrankenPHP + Laravel Octane runtime
#
# Runtime is controlled by two environment variables (see docker/entrypoint.sh):
#
#   ROLE          = web | worker | all     (default: all)
#   SKIP_MIGRATE  = 0 | 1                  (default: 1 — safe)
#
# Exactly ONE container in a multi-container deployment must run with
# SKIP_MIGRATE=0 so the schema is migrated exactly once. All others must
# set SKIP_MIGRATE=1. See docs/mil-std-498/SIP.md §3.4 for patterns.
#
# Runtime secrets (APP_KEY, DB credentials, TICKET_TOKEN_PEPPER, Stripe keys,
# S3 credentials) MUST be injected via environment variables by the
# orchestrator. They are never baked into any image layer.
#
# NOTE on base image pinning: the tags below should be replaced by immutable
# @sha256:... digests before tagging a production release. Digest pinning is
# required by SSS ENV-DEP-010 but is intentionally left as a release-time
# operation so the Dockerfile remains readable in source control.

# =============================================================================
# Stage 1: PHP dependency install + Wayfinder type generation
# =============================================================================
FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN mkdir -p bootstrap/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs
RUN composer dump-autoload --optimize --classmap-authoritative

# Wayfinder TypeScript generation needs a bootable framework. We supply a
# throwaway APP_KEY via build ARG so the real runtime APP_KEY is never
# confused with it. This key has no security significance.
ARG BUILD_APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
RUN APP_KEY=${BUILD_APP_KEY} \
    APP_ENV=local \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory: \
    php artisan wayfinder:generate

# =============================================================================
# Stage 2: Frontend asset build
# =============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY . .

# Overlay with Wayfinder-generated TypeScript (git-ignored, produced in stage 1).
COPY --from=deps /app/resources/js/actions   ./resources/js/actions
COPY --from=deps /app/resources/js/routes    ./resources/js/routes
COPY --from=deps /app/resources/js/wayfinder ./resources/js/wayfinder

# Vite's Wayfinder plugin calls `php artisan wayfinder:generate --with-form`
# at startup. Types are already present above; stub php so the plugin call
# exits cleanly during `npm run build`.
RUN printf '#!/bin/sh\nexit 0\n' > /usr/local/bin/php && chmod +x /usr/local/bin/php

RUN npm run build

# =============================================================================
# Stage 3: Production image (FrankenPHP + Octane)
# =============================================================================
FROM dunglas/frankenphp:php8.5-alpine AS production

LABEL org.opencontainers.image.title="LanCore" \
      org.opencontainers.image.description="LanCore — LAN party management application" \
      org.opencontainers.image.url="https://lan-software.de" \
      org.opencontainers.image.source="https://github.com/lan-software/lancore" \
      org.opencontainers.image.vendor="Lan-Software.de" \
      org.opencontainers.image.authors="Markus Kohn <post@markus-kohn.de>" \
      org.opencontainers.image.licenses="AGPL-3.0" \
      org.opencontainers.image.base.name="dunglas/frankenphp:php8.5-alpine"

# System dependencies. su-exec lets entrypoint.sh drop from root to www-data
# before exec'ing supervisord; curl is retained for the HEALTHCHECK probe.
RUN apk add --no-cache supervisor curl su-exec

# PHP extensions — install-php-extensions resolves all transitive deps.
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    bcmath \
    mbstring \
    exif \
    pcntl \
    zip \
    gd \
    opcache \
    intl \
    redis

# PHP production configuration
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# FrankenPHP Caddyfile
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Supervisor configs — one combined + two split-role configs.
# The ROLE env var in entrypoint.sh selects which one runs.
COPY docker/supervisor/supervisord.conf        /etc/supervisor/conf.d/supervisord.conf
COPY docker/supervisor/supervisord-web.conf    /etc/supervisor/conf.d/supervisord-web.conf
COPY docker/supervisor/supervisord-worker.conf /etc/supervisor/conf.d/supervisord-worker.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

# Copy application from build stages
COPY --from=deps     /app              /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Permissions + runtime dirs for supervisor.
RUN mkdir -p storage/framework/sessions storage/framework/views \
             storage/framework/cache storage/logs bootstrap/cache \
             /var/log/supervisor /var/run/supervisor \
 && chown -R www-data:www-data storage bootstrap/cache /var/log/supervisor /var/run/supervisor

EXPOSE 80 443

# Healthcheck hits Laravel's built-in /up endpoint. Start period covers
# Octane cold boot plus an optional one-shot migration on the migrator
# container.
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

# Default tunables — overridable at runtime via `docker run -e`.
ENV ROLE=all \
    SKIP_MIGRATE=1 \
    OCTANE_WORKERS=auto \
    OCTANE_MAX_REQUESTS=500

# The entrypoint itself needs root briefly (chown of runtime dirs) then
# drops privileges to www-data via su-exec before supervisord starts.
ENTRYPOINT ["/entrypoint.sh"]
