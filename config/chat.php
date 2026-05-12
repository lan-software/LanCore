<?php

return [

    'rate_limits' => [
        // Short-burst limit: max attempts per second window.
        'short' => [
            'max' => env('CHAT_RATE_LIMIT_SHORT_MAX', 5),
            'decay_seconds' => env('CHAT_RATE_LIMIT_SHORT_DECAY', 10),
        ],
        // Sustained limit: max attempts per minute.
        'long' => [
            'max' => env('CHAT_RATE_LIMIT_LONG_MAX', 30),
            'decay_seconds' => env('CHAT_RATE_LIMIT_LONG_DECAY', 60),
        ],
    ],

    // Identical body from same user in same room within this window is rejected.
    'duplicate_window' => env('CHAT_DUPLICATE_WINDOW', 10),

    // Maximum body length after whitespace normalization.
    'max_length' => env('CHAT_MAX_LENGTH', 2000),

];
