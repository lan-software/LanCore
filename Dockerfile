#syntax=docker/dockerfile:1.4

# ============================
# Stage 1: Frontend asset build
# ============================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY . .

# Wayfinder Vite plugin calls `php artisan wayfinder:generate` at build time.
# Types are already committed; stub php so the plugin exits cleanly.
RUN printf '#!/bin/sh\nexit 0\n' > /usr/local/bin/php && chmod +x /usr/local/bin/php

RUN npm run build

# ============================
# Stage 2: PHP dependency install
# ============================
FROM composer:2 AS composer

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

# ============================
# Stage 3: Production image (FrankenPHP + Octane)
# ============================
FROM dunglas/frankenphp:php8.5-alpine AS production

LABEL org.opencontainers.image.source="https://github.com/lan-software/lancore"
LABEL org.opencontainers.image.description="LanCore Laravel Application"
LABEL org.opencontainers.image.licenses="MIT"

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
COPY --from=composer /app /var/www/html
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
