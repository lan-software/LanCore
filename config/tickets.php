<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HMAC Pepper
    |--------------------------------------------------------------------------
    |
    | Server-side secret used to derive the deterministic nonce_hash from a
    | per-ticket random nonce. Rotating the pepper invalidates all in-flight
    | tokens; rotate via a coordinated maintenance window.
    |
    */
    'pepper' => env('TICKET_TOKEN_PEPPER'),

    'signing' => [
        'active_kid' => env('TICKET_SIGNING_ACTIVE_KID'),
        'verify_kids' => array_filter(explode(',', (string) env('TICKET_SIGNING_VERIFY_KIDS', ''))),
        'keys_path' => storage_path('keys/ticket_signing'),
    ],

    'token' => [
        'version' => 'LCT1',
        'grace_period_hours' => (int) env('TICKET_TOKEN_GRACE_HOURS', 6),
    ],

];
