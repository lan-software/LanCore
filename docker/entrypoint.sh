#!/bin/sh
set -e

echo "Starting LanCore..."

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations
php artisan migrate --force

# Start Supervisor — manages Octane (FrankenPHP), queue workers, and scheduler
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
