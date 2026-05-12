<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment provider fee schedules
    |--------------------------------------------------------------------------
    |
    | Per-provider fee description used both for display on the External APIs
    | page and as a fallback estimate when the provider hasn't reported an
    | actual fee yet. `percentage` is in basis points of a percent (e.g. 150 =
    | 1.50%); `fixed_cents` is in the minor currency unit. Set both to 0 for
    | "no fee" providers (e.g. on-site cash).
    |
    */
    'provider_fees' => [
        'stripe' => [
            'percentage' => env('SHOP_FEE_STRIPE_PERCENT', 150),
            'fixed_cents' => env('SHOP_FEE_STRIPE_FIXED', 25),
            'currency' => env('CASHIER_CURRENCY', 'eur'),
            'note' => env('SHOP_FEE_STRIPE_NOTE', 'Standard EEA card rate. Non-EEA cards are billed at a higher rate by Stripe.'),
        ],
        'paypal' => [
            'percentage' => env('SHOP_FEE_PAYPAL_PERCENT', 249),
            'fixed_cents' => env('SHOP_FEE_PAYPAL_FIXED', 35),
            'currency' => env('CASHIER_CURRENCY', 'eur'),
            'note' => env('SHOP_FEE_PAYPAL_NOTE', 'PayPal "Commercial Transactions in EEA" rate. Cross-border transactions are billed at a higher rate.'),
        ],
        'on_site' => [
            'percentage' => 0,
            'fixed_cents' => 0,
            'currency' => env('CASHIER_CURRENCY', 'eur'),
            'note' => 'On-site payments have no platform fee.',
        ],
    ],
];
