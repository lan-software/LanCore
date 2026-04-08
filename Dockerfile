#syntax=docker/dockerfile:1.7

# =============================================================================
# LanCore — Production Docker image
# =============================================================================
#
# Three-stage build:
#   1. deps      — composer install + Wayfinder TypeScript generation
#   2. frontend  — Vite asset build (Node 22)
#   3. production — inherits ghcr.io/lan-software/lanbase (FrankenPHP + Octane)
#
# Runtime is controlled by environment variables handled by LanBase's baked
# entrypoint. See LanBase/README.md for the full contract:
#
#   FLAVOR        = octane | server       (LanCore: octane — the default)
#   ROLE          = web | worker | all    (default: all)
#   SKIP_MIGRATE  = 0 | 1                 (default: 1 — safe)
#
# Exactly ONE container in a multi-container deployment must run with
# SKIP_MIGRATE=0 so the schema is migrated exactly once. All others must
# set SKIP_MIGRATE=1. See docs/mil-std-498/SIP.md §3.4 for patterns.
#
# Runtime secrets (APP_KEY, DB credentials, TICKET_TOKEN_PEPPER, Stripe keys,
# S3 credentials) MUST be injected via environment variables by the
# orchestrator. They are never baked into any image layer.
#
# NOTE on base image pinning: the LanBase tag below should be replaced by an
# immutable :php8.5-sha-<shortsha> tag (or @sha256:... digest) before tagging
# a production release. Digest pinning is required by SSS ENV-DEP-010.

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
# Stage 3: Production image (LanBase = FrankenPHP + Octane runtime)
# =============================================================================
FROM ghcr.io/lan-software/lanbase:php8.5 AS production

LABEL org.opencontainers.image.title="LanCore" \
      org.opencontainers.image.description="LanCore — LAN party management application" \
      org.opencontainers.image.url="https://lan-software.de" \
      org.opencontainers.image.source="https://github.com/lan-software/lancore" \
      org.opencontainers.image.vendor="Lan-Software.de" \
      org.opencontainers.image.authors="Markus Kohn <post@markus-kohn.de>" \
      org.opencontainers.image.licenses="AGPL-3.0"

# Copy application from build stages. LanBase provides the runtime; this
# image just layers in the app code and compiled assets.
COPY --from=deps     /app              /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Fix ownership of runtime-writable dirs. Everything else (PHP, extensions,
# supervisor, entrypoint, healthcheck, ENV defaults) is inherited from LanBase.
RUN chown -R www-data:www-data storage bootstrap/cache
