<?php

/*
 * Declarative integration-app configuration.
 *
 * @see docs/mil-std-498/SRS.md INT-F-011, INT-F-012, INT-F-013, INT-F-014
 * @see docs/mil-std-498/SSDD.md §5.4.5
 * @see docs/mil-std-498/IRS.md  §3.5a IF-INTCFG
 *
 * Every value here is driven by an environment variable. The Helm umbrella
 * chart populates the env from operator `values.yaml` plus a shared
 * auto-generated seed Secret. Operators should NOT edit this file — change
 * values via env (or, in the Helm world, via `values.yaml`).
 *
 * Slugs listed in the `apps` array are reconciled by `integrations:sync`:
 * each row is upserted; its tokens and subscribed webhooks are wiped and
 * recreated from this configuration on every reconciliation. Slugs NOT
 * listed here remain UI/Artisan-managed and are left untouched.
 */

return [
    'domain' => env('LANCORE_DOMAIN'),

    'lancore_host' => env('LANCORE_HOST'),

    'satellite_host_style' => env('LANCORE_SATELLITE_HOST_STYLE', 'flat'),

    'scheme' => env('LANCORE_SATELLITE_SCHEME', 'https'),

    'reconcile_on_boot' => env('LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT', false),

    'release' => [
        'name' => env('LANCORE_RELEASE_NAME'),
        'revision' => env('LANCORE_RELEASE_REVISION'),
    ],

    'apps' => [

        'lanbrackets' => [
            'name' => 'LanBrackets',
            'description' => 'Tournament bracket management',
            'host' => env('LANBRACKETS_HOST'),
            'callback_path' => env('LANBRACKETS_CALLBACK_PATH', '/auth/callback'),
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'nav_url' => env('LANBRACKETS_NAV_URL'),
            'nav_icon' => 'trophy',
            'nav_label' => 'Brackets',
            'send_announcements' => env('LANBRACKETS_SEND_ANNOUNCEMENTS', true),
            'announcement_path' => env('LANBRACKETS_ANNOUNCEMENT_PATH', '/api/webhooks/lancore/announcements'),
            'send_role_updates' => env('LANBRACKETS_SEND_ROLE_UPDATES', true),
            'roles_path' => env('LANBRACKETS_ROLES_PATH', '/api/webhooks/lancore/roles'),
            'token' => env('LANBRACKETS_LANCORE_TOKEN'),
            'announcement_webhook_secret' => env('LANBRACKETS_ANNOUNCEMENT_WEBHOOK_SECRET'),
            'roles_webhook_secret' => env('LANBRACKETS_ROLES_WEBHOOK_SECRET'),
        ],

        'lanentrance' => [
            'name' => 'LanEntrance',
            'description' => 'Event check-in and door control',
            'host' => env('LANENTRANCE_HOST'),
            'callback_path' => env('LANENTRANCE_CALLBACK_PATH', '/auth/callback'),
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'nav_url' => env('LANENTRANCE_NAV_URL'),
            'nav_icon' => 'door-open',
            'nav_label' => 'Entrance',
            'send_announcements' => env('LANENTRANCE_SEND_ANNOUNCEMENTS', false),
            'announcement_path' => env('LANENTRANCE_ANNOUNCEMENT_PATH', '/api/webhooks/lancore/announcements'),
            'send_role_updates' => env('LANENTRANCE_SEND_ROLE_UPDATES', true),
            'roles_path' => env('LANENTRANCE_ROLES_PATH', '/api/webhooks/lancore/roles'),
            'token' => env('LANENTRANCE_LANCORE_TOKEN'),
            'announcement_webhook_secret' => env('LANENTRANCE_ANNOUNCEMENT_WEBHOOK_SECRET'),
            'roles_webhook_secret' => env('LANENTRANCE_ROLES_WEBHOOK_SECRET'),
        ],

        'lanshout' => [
            'name' => 'LanShout',
            'description' => 'Event announcements and chat',
            'host' => env('LANSHOUT_HOST'),
            'callback_path' => env('LANSHOUT_CALLBACK_PATH', '/auth/callback'),
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'nav_url' => env('LANSHOUT_NAV_URL'),
            'nav_icon' => 'megaphone',
            'nav_label' => 'Shout',
            'send_announcements' => env('LANSHOUT_SEND_ANNOUNCEMENTS', true),
            'announcement_path' => env('LANSHOUT_ANNOUNCEMENT_PATH', '/api/webhooks/lancore/announcements'),
            'send_role_updates' => env('LANSHOUT_SEND_ROLE_UPDATES', true),
            'roles_path' => env('LANSHOUT_ROLES_PATH', '/api/webhooks/lancore/roles'),
            'token' => env('LANSHOUT_LANCORE_TOKEN'),
            'announcement_webhook_secret' => env('LANSHOUT_ANNOUNCEMENT_WEBHOOK_SECRET'),
            'roles_webhook_secret' => env('LANSHOUT_ROLES_WEBHOOK_SECRET'),
        ],

        'lanhelp' => [
            'name' => 'LanHelp',
            'description' => 'Event staff helpdesk',
            'host' => env('LANHELP_HOST'),
            'callback_path' => env('LANHELP_CALLBACK_PATH', '/auth/callback'),
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'nav_url' => env('LANHELP_NAV_URL'),
            'nav_icon' => 'life-buoy',
            'nav_label' => 'Help',
            'send_announcements' => env('LANHELP_SEND_ANNOUNCEMENTS', true),
            'announcement_path' => env('LANHELP_ANNOUNCEMENT_PATH', '/api/webhooks/lancore/announcements'),
            'send_role_updates' => env('LANHELP_SEND_ROLE_UPDATES', true),
            'roles_path' => env('LANHELP_ROLES_PATH', '/api/webhooks/lancore/roles'),
            'token' => env('LANHELP_LANCORE_TOKEN'),
            'announcement_webhook_secret' => env('LANHELP_ANNOUNCEMENT_WEBHOOK_SECRET'),
            'roles_webhook_secret' => env('LANHELP_ROLES_WEBHOOK_SECRET'),
        ],

    ],
];
