<?php

use App\Http\Middleware\EnsureValidMetricsToken;
use Spatie\Prometheus\Actions\RenderCollectorsAction;
use Spatie\Prometheus\Http\Middleware\AllowIps;

return [
    /*
     * Toggle the entire metrics endpoint. Disable for environments where
     * you do not want any metrics exposed.
     */
    'enabled' => (bool) env('METRICS_ENABLED', true),

    /*
     * /metrics is the Prometheus convention and the default scrape path.
     */
    'urls' => [
        'default' => 'metrics',
    ],

    /*
     * Bearer token required on every scrape request.
     * Set METRICS_TOKEN to a cryptographically random string, e.g.:
     *   openssl rand -hex 32
     * When empty the endpoint returns 503 — safe-off by default.
     */
    'token' => env('METRICS_TOKEN'),

    /*
     * Optional IP allow-list. Requests from IPs not in this list receive 403.
     * Accepts comma-separated IPs via env: METRICS_ALLOWED_IPS=10.0.0.5,10.0.0.6
     * Leave empty to allow all IPs (bearer-token protection still applies).
     */
    'allowed_ips' => array_values(array_filter(
        explode(',', (string) env('METRICS_ALLOWED_IPS', ''))
    )),

    /*
     * Prometheus metric namespace prepended to every metric name.
     */
    'default_namespace' => 'app',

    /*
     * Middleware applied to the /metrics URL.
     * IP allow-list runs first, bearer-token check second.
     */
    'middleware' => [
        AllowIps::class,
        EnsureValidMetricsToken::class,
    ],

    /*
     * You can override these classes to customise low-level behaviour.
     */
    'actions' => [
        'render_collectors' => RenderCollectorsAction::class,
    ],

    /*
     * Whether to wipe the metric storage after each render.
     */
    'wipe_storage_after_rendering' => false,

    /*
     * Cache store used to persist counter/gauge values between requests.
     * Use 'redis' in production (shared across Octane workers, survives restarts).
     * Use null (InMemory) or 'array' during testing.
     */
    'cache' => env('METRICS_CACHE_STORE', 'redis'),
];
