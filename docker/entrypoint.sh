#!/bin/sh
set -eu

# ROLE selects which supervisor config to run:
#   all    — Octane + Horizon + scheduler in one container
#   web    — Octane only (pair with a separate worker container)
#   worker — Horizon + scheduler only (pair with a separate web container)
#
# SKIP_MIGRATE=1 disables the migrate --force step. In multi-container
# deployments EXACTLY ONE container (the designated migrator) must set
# SKIP_MIGRATE=0; every other container must set SKIP_MIGRATE=1 to avoid
# racing on schema changes. Default is 1 (safe).
#
# OCTANE_WORKERS / OCTANE_MAX_REQUESTS tune the Octane worker pool without
# rebuilding the image — referenced from supervisord-{web,all}.conf.

ROLE="${ROLE:-all}"
SKIP_MIGRATE="${SKIP_MIGRATE:-1}"
export OCTANE_WORKERS="${OCTANE_WORKERS:-auto}"
export OCTANE_MAX_REQUESTS="${OCTANE_MAX_REQUESTS:-500}"

log() { echo "[entrypoint] $*"; }
die() { echo "[entrypoint] ERROR: $*" >&2; exit 1; }

log "Starting LanCore (role=${ROLE}, skip_migrate=${SKIP_MIGRATE}, workers=${OCTANE_WORKERS})"

# Runtime sanity checks — fail fast with a clear message rather than
# letting supervisord loop on a broken process.
[ -n "${APP_KEY:-}" ] || die "APP_KEY must be set at runtime (never baked into the image)"

# Ensure writable runtime dirs exist with correct ownership.
mkdir -p /var/run/supervisor /var/log/supervisor \
         storage/framework/sessions storage/framework/views \
         storage/framework/cache storage/logs bootstrap/cache
chown -R www-data:www-data /var/run/supervisor /var/log/supervisor storage bootstrap/cache

# Cache configuration for production — abort if any step fails.
su-exec www-data php artisan config:cache    || die "config:cache failed"
su-exec www-data php artisan route:cache     || die "route:cache failed"
su-exec www-data php artisan view:cache      || die "view:cache failed"
su-exec www-data php artisan event:cache     || die "event:cache failed"

if [ "${SKIP_MIGRATE}" != "1" ]; then
    log "Running database migrations (designated migrator)"
    su-exec www-data php artisan migrate --force || die "migrate --force failed"
else
    log "Skipping migrations (SKIP_MIGRATE=1)"
fi

case "${ROLE}" in
    web)    CONF=/etc/supervisor/conf.d/supervisord-web.conf ;;
    worker) CONF=/etc/supervisor/conf.d/supervisord-worker.conf ;;
    all)    CONF=/etc/supervisor/conf.d/supervisord.conf ;;
    *)      die "Unknown ROLE: ${ROLE} (expected: web | worker | all)" ;;
esac

log "Exec supervisord with ${CONF}"
exec su-exec www-data /usr/bin/supervisord -c "${CONF}"
