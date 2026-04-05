<?php
/**
 * RezlyX Application Configuration
 *
 * @package RezlyX
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'RezlyX'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    | local, staging, production
    */
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Seoul'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale
    |--------------------------------------------------------------------------
    */
    'locale' => env('APP_LOCALE', 'ko'),
    'fallback_locale' => env('FALLBACK_LOCALE', 'en'),
    'supported_locales' => explode(',', env('SUPPORTED_LOCALES', 'ko,en,ja')),

    /*
    |--------------------------------------------------------------------------
    | Admin Path
    |--------------------------------------------------------------------------
    */
    'admin_path' => env('ADMIN_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // Core Providers
        RzxLib\Core\Providers\DatabaseServiceProvider::class,
        RzxLib\Core\Providers\CacheServiceProvider::class,
        RzxLib\Core\Providers\SessionServiceProvider::class,
        RzxLib\Core\Providers\AuthServiceProvider::class,
        RzxLib\Core\Providers\ValidationServiceProvider::class,
        RzxLib\Core\Providers\LoggerServiceProvider::class,

        // Module Providers
        RzxLib\Modules\I18n\I18nServiceProvider::class,
        RzxLib\Modules\Payment\PaymentServiceProvider::class,
        RzxLib\Modules\Notification\NotificationServiceProvider::class,
        RzxLib\Modules\Points\PointsServiceProvider::class,

        // Application Providers
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    */
    'aliases' => [
        'App' => RzxLib\Core\Facades\App::class,
        'Auth' => RzxLib\Core\Facades\Auth::class,
        'Cache' => RzxLib\Core\Facades\Cache::class,
        'DB' => RzxLib\Core\Facades\DB::class,
        'Lang' => RzxLib\Core\Facades\Lang::class,
        'Log' => RzxLib\Core\Facades\Log::class,
        'Request' => RzxLib\Core\Facades\Request::class,
        'Response' => RzxLib\Core\Facades\Response::class,
        'Session' => RzxLib\Core\Facades\Session::class,
        'Validator' => RzxLib\Core\Facades\Validator::class,
        'View' => RzxLib\Core\Facades\View::class,
    ],
];
