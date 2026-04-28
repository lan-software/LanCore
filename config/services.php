<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('APP_URL'),
    ],

    'plausible' => [
        'enabled' => env('PLAUSIBLE_ENABLED', false),
        'domain' => env('PLAUSIBLE_DOMAIN'),
        'src' => env('PLAUSIBLE_SRC', 'https://plausible.io/js/script.js'),
    ],

    /*
    | Steam OpenID 2.0 (via socialiteproviders/steam). The package piggybacks on
    | Socialite's OAuth2 abstract provider, so it requires `client_id` and
    | `client_secret` keys to exist — it stores the Steam Web API key in
    | `client_secret`. `client_id` is unused by Steam's OpenID flow.
    | `allowed_hosts` is matched against the `openid.return_to` host on callback;
    | leave empty to disable the check (recommended only locally).
    */
    'steam' => [
        'client_id' => null,
        'client_secret' => env('STEAM_API_KEY'),
        'redirect' => env('STEAM_REDIRECT_URI'),
        'allowed_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('STEAM_ALLOWED_HOSTS', '')),
        ))),
    ],

];
