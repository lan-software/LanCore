<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Listmonk Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Listmonk newsletter manager (https://listmonk.app)
    | that owns campaign content and per-list subscriber state. LanCore acts
    | only as the ingress (subscriber onboarding + per-list opt-in) and as a
    | local mirror of list metadata.
    |
    | Authentication uses Listmonk's HTTP Basic auth (admin user/password
    | pair); set both `LISTMONK_USERNAME` and `LISTMONK_PASSWORD` in `.env`.
    |
    */

    'enabled' => env('LISTMONK_ENABLED', false),

    'base_url' => env('LISTMONK_BASE_URL'),

    'username' => env('LISTMONK_USERNAME'),

    'password' => env('LISTMONK_PASSWORD'),

    'timeout' => env('LISTMONK_TIMEOUT', 10),

    'retries' => env('LISTMONK_RETRIES', 2),

    'retry_delay' => env('LISTMONK_RETRY_DELAY', 200),

    /*
    | When true, subscriptions are pre-confirmed in Listmonk (no
    | confirmation email is sent). Defaults to false so the public
    | `/countdown` form requires a click-through opt-in.
    */
    'preconfirm_subscriptions' => env('LISTMONK_PRECONFIRM', false),

];
