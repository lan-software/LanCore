<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LanBrackets Integration
    |--------------------------------------------------------------------------
    |
    | Toggle the LanBrackets integration on or off. When disabled, competition
    | sync to LanBrackets is skipped and bracket links are hidden.
    |
    */
    'enabled' => env('LANBRACKETS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | LanBrackets URLs
    |--------------------------------------------------------------------------
    |
    | base_url     — Browser-facing URL used for bracket view links.
    | internal_url — Server-to-server URL used for API calls (Docker fix).
    |                Falls back to base_url if not set.
    |
    */
    'base_url' => env('LANBRACKETS_BASE_URL', 'http://localhost'),

    'internal_url' => env('LANBRACKETS_INTERNAL_URL') ?? env('LANBRACKETS_BASE_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */
    'token' => env('LANBRACKETS_TOKEN'),

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
