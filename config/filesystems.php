<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Semantic Storage Roles
    |--------------------------------------------------------------------------
    |
    | LanCore separates storage into two semantic roles: a "public" role for
    | non-sensitive assets (organization logos, public images) and a "private"
    | role for sensitive artifacts (invoices, receipts, ticket PDFs). Each role
    | is independently routed to one of the disks defined below, allowing
    | operators to put sensitive files into a separate, non-public bucket.
    |
    */

    'public_disk' => env('FILESYSTEM_PUBLIC_DISK', 'public'),
    'private_disk' => env('FILESYSTEM_PRIVATE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'anonymous_bucket_access' => env('AWS_ANONYMOUS_BUCKET_ACCESS', false),
            'throw' => false,
            'report' => false,
        ],

        's3_public' => [
            'driver' => 's3',
            'key' => env('AWS_PUBLIC_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('AWS_PUBLIC_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('AWS_PUBLIC_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'bucket' => env('AWS_PUBLIC_BUCKET', env('AWS_BUCKET')),
            'url' => env('AWS_PUBLIC_URL', env('AWS_URL')),
            'endpoint' => env('AWS_PUBLIC_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('AWS_PUBLIC_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'anonymous_bucket_access' => env('AWS_PUBLIC_ANONYMOUS_BUCKET_ACCESS', env('AWS_ANONYMOUS_BUCKET_ACCESS', false)),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3_private' => [
            'driver' => 's3',
            'key' => env('AWS_PRIVATE_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('AWS_PRIVATE_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('AWS_PRIVATE_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'bucket' => env('AWS_PRIVATE_BUCKET', env('AWS_BUCKET')),
            'url' => env('AWS_PRIVATE_URL', env('AWS_URL')),
            'endpoint' => env('AWS_PRIVATE_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('AWS_PRIVATE_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
