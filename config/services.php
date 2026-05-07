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

    'stats' => [
        'login' => env('STATS_LOGIN', 'admin'),
        'password' => env('STATS_PASSWORD', 'secret'),
        'jwt_secret' => env('STATS_JWT_SECRET', env('APP_KEY')),
        'jwt_ttl' => env('STATS_JWT_TTL', 3600),
        'rate_limit' => [
            'driver' => env('STATS_RATE_LIMIT_DRIVER', 'redis'),
            'redis_connection' => env('STATS_RATE_LIMIT_REDIS_CONNECTION', 'cache'),
            'max_attempts' => env('STATS_RATE_LIMIT_MAX_ATTEMPTS', 5),
            'window_seconds' => env('STATS_RATE_LIMIT_WINDOW_SECONDS', 60),
        ],
    ],

    'socket_io' => [
        'enabled' => env('SOCKET_IO_ENABLED', false),
        'client_url' => env('SOCKET_IO_CLIENT_URL'),
        'server_url' => env('SOCKET_IO_SERVER_URL', 'http://127.0.0.1:6001'),
        'internal_token' => env('SOCKET_IO_INTERNAL_TOKEN'),
    ],

];
