<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idle Threshold (seconds)
    |--------------------------------------------------------------------------
    |
    | A user whose last heartbeat is older than this many seconds is considered
    | idle. Default: 5 minutes.
    |
    */

    'idle_after' => env('PRESENCE_IDLE_AFTER', 300),

    /*
    |--------------------------------------------------------------------------
    | Offline Threshold (seconds)
    |--------------------------------------------------------------------------
    |
    | A user whose last heartbeat is older than this many seconds is considered
    | offline. This is also the TTL applied to the Redis presence key, so
    | offline users disappear from the store entirely. Default: 30 minutes.
    |
    */

    'offline_after' => env('PRESENCE_OFFLINE_AFTER', 1800),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Redis connection name used for storing presence heartbeats. Must resolve
    | to a Redis connection configured in config/database.php.
    |
    */

    'cache_store' => env('PRESENCE_CACHE_STORE', 'default'),

];
