<?php

/*
| Bridges the LanBrackets Competition sync config with the wider integration
| config in config/integrations.php. The two were previously disjoint —
| `LANBRACKETS_HOST` / `LANBRACKETS_LANCORE_TOKEN` enabled the OAuth/webhook
| side of LanBrackets but did NOT enable Competition sync (which read
| `LANBRACKETS_ENABLED`, `LANBRACKETS_BASE_URL`, `LANBRACKETS_TOKEN`), so a
| fully-wired integration was still flagged "disabled" for sync purposes and
| failed tournaments could not be created on the LanBrackets side.
|
| The fallbacks below make `LANBRACKETS_HOST` (with optional protocol) the
| single source of truth: if the integration is configured, Competition sync
| is enabled by default.
*/

$host = env('LANBRACKETS_HOST');
$baseUrl = env('LANBRACKETS_BASE_URL');
$internalUrl = env('LANBRACKETS_INTERNAL_URL');

if ($baseUrl === null && $host !== null) {
    $baseUrl = str_contains($host, '://') ? $host : 'http://'.$host;
}

if ($internalUrl === null) {
    $internalUrl = $baseUrl;
}

return [
    /*
    |--------------------------------------------------------------------------
    | LanBrackets Integration
    |--------------------------------------------------------------------------
    |
    | Toggle the LanBrackets integration on or off. When disabled, competition
    | sync to LanBrackets is skipped and bracket links are hidden. Defaults to
    | enabled whenever the integration host is configured.
    |
    */
    'enabled' => env('LANBRACKETS_ENABLED', $host !== null),

    /*
    |--------------------------------------------------------------------------
    | LanBrackets URLs
    |--------------------------------------------------------------------------
    |
    | base_url     — Browser-facing URL used for bracket view links.
    | internal_url — Server-to-server URL used for API calls (Docker fix).
    |                Falls back to base_url if not set.
    |
    | Both fall back to `LANBRACKETS_HOST` (with `http://` auto-prefixed)
    | so the integration config in config/integrations.php is the canonical
    | location for the host.
    |
    */
    'base_url' => $baseUrl ?? 'http://localhost',

    'internal_url' => $internalUrl ?? 'http://localhost',

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | `LANBRACKETS_TOKEN` is the Competition sync token. Falls back to
    | `LANBRACKETS_LANCORE_TOKEN` (used by config/integrations.php) so a
    | single token covers both sides of the integration.
    |
    */
    'token' => env('LANBRACKETS_TOKEN') ?? env('LANBRACKETS_LANCORE_TOKEN'),

    'webhook_secret' => env('LANBRACKETS_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Tuning
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('LANBRACKETS_TIMEOUT', 5),

    'retries' => (int) env('LANBRACKETS_RETRIES', 2),

    'retry_delay' => (int) env('LANBRACKETS_RETRY_DELAY', 100),
];
