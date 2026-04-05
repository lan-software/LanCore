<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TMT2 Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for the TMT2 (Tournament Match Tracker 2) backend service
    | that manages CS2 match supervision, map veto, and RCON communication.
    |
    */

    'enabled' => env('TMT2_ENABLED', false),

    'base_url' => env('TMT2_BASE_URL', 'http://localhost:8080'),

    'token' => env('TMT2_TOKEN'),

    'timeout' => env('TMT2_TIMEOUT', 5),

    'retries' => env('TMT2_RETRIES', 2),

    'retry_delay' => env('TMT2_RETRY_DELAY', 100),

];
