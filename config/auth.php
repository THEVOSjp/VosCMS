<?php
/**
 * RezlyX Authentication Configuration
 *
 * @package RezlyX
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'users' => [
            'driver' => 'database',
            'table' => 'users',
        ],

        'admins' => [
            'driver' => 'database',
            'table' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Settings
    |--------------------------------------------------------------------------
    */
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'ttl' => env('JWT_TTL', 60), // minutes
        'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 weeks
        'algo' => 'HS256',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */
    'password_timeout' => 10800, // 3 hours

    /*
    |--------------------------------------------------------------------------
    | Social Login Providers
    |--------------------------------------------------------------------------
    */
    'social' => [
        'google' => [
            'enabled' => (bool) env('GOOGLE_LOGIN_ENABLED', false),
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        ],
        'kakao' => [
            'enabled' => (bool) env('KAKAO_LOGIN_ENABLED', false),
            'client_id' => env('KAKAO_CLIENT_ID'),
            'client_secret' => env('KAKAO_CLIENT_SECRET'),
        ],
        'naver' => [
            'enabled' => (bool) env('NAVER_LOGIN_ENABLED', false),
            'client_id' => env('NAVER_CLIENT_ID'),
            'client_secret' => env('NAVER_CLIENT_SECRET'),
        ],
        'line' => [
            'enabled' => (bool) env('LINE_LOGIN_ENABLED', false),
            'client_id' => env('LINE_CLIENT_ID'),
            'client_secret' => env('LINE_CLIENT_SECRET'),
        ],
    ],
];
