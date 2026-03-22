#syntax=docker/dockerfile:1.4

# ============================
# Stage 1: PHP dependency install + Wayfinder type generation
# ============================
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
RUN composer dump-autoload --optimize --classmap-authoritative

# Generate Wayfinder TypeScript files (actions/, routes/) so the frontend
# build stage has them. These are git-ignored generated artefacts.
RUN mkdir -p bootstrap/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs \
    && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    APP_ENV=local \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory: \
    php artisan wayfinder:generate

# ============================
# Stage 2: Frontend asset build
# ============================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY . .

# Overlay with Wayfinder-generated TypeScript (git-ignored, produced in the deps stage)
COPY --from=deps /app/resources/js/actions ./resources/js/actions
COPY --from=deps /app/resources/js/routes ./resources/js/routes

# Vite plugin calls `php artisan wayfinder:generate --with-form` at startup.
# Types are already present above; stub php so the plugin call exits cleanly.
RUN printf '#!/bin/sh\nexit 0\n' > /usr/local/bin/php && chmod +x /usr/local/bin/php

RUN npm run build

# ============================
# Stage 3: Production image (FrankenPHP + Octane)
# ============================
FROM dunglas/frankenphp:php8.5-alpine AS production

LABEL org.opencontainers.image.title="LanCore" \
      org.opencontainers.image.description="LanCore — LAN party management application" \
      org.opencontainers.image.url="https://lan-software.de" \
      org.opencontainers.image.source="https://github.com/lan-software/lancore" \
      org.opencontainers.image.vendor="Lan-Software.de" \
      org.opencontainers.image.authors="Markus Kohn <post@markus-kohn.de>" \
      org.opencontainers.image.licenses="AGPL-3.0" \
      org.opencontainers.image.base.name="dunglas/frankenphp:php8.5-alpine"

# System dependencies
RUN apk add --no-cache supervisor curl

# PHP extensions — install-php-extensions resolves all transitive deps automatically
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
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# FrankenPHP Caddyfile
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Supervisor (queue workers + scheduler; Octane is managed here too)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

# Copy application from build stages
COPY --from=deps /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Permissions
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && mkdir -p /var/log/supervisor

EXPOSE 80 443

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]


# ============================
# Stage 3: Production image (FrankenPHP + Octane)
# ============================
FROM dunglas/frankenphp:php8.5-alpine AS production

LABEL org.opencontainers.image.title="LanCore" \
      org.opencontainers.image.description="LanCore — LAN party management application" \
      org.opencontainers.image.url="https://lan-software.de" \
      org.opencontainers.image.source="https://github.com/lan-software/lancore" \
      org.opencontainers.image.vendor="Lan-Software.de" \
      org.opencontainers.image.authors="Markus Kohn <post@markus-kohn.de>" \
      org.opencontainers.image.licenses="AGPL-3.0" \
      org.opencontainers.image.base.name="dunglas/frankenphp:php8.5-alpine"

# System dependencies
RUN apk add --no-cache supervisor curl

# PHP extensions — install-php-extensions resolves all transitive deps automatically
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
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# FrankenPHP Caddyfile
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Supervisor (queue workers + scheduler; Octane is managed here too)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

# Copy application from build stages
COPY --from=deps /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Permissions
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && mkdir -p /var/log/supervisor

EXPOSE 80 443

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
