<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expiry reminder
    |--------------------------------------------------------------------------
    */

    'expiry_reminder_days' => (int) env('SUBSCRIPTION_EXPIRY_REMINDER_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Auto renew
    |--------------------------------------------------------------------------
    */

    'auto_renew' => [
        'enabled' => (bool) env('SUBSCRIPTION_AUTO_RENEW_ENABLED', true),
        'max_attempts' => (int) env('SUBSCRIPTION_AUTO_RENEW_MAX_ATTEMPTS', 3),
        'retry_hours' => (int) env('SUBSCRIPTION_AUTO_RENEW_RETRY_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => env('SUBSCRIPTION_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Frontend paths
    |--------------------------------------------------------------------------
    */

    'plans_path' => env('SUBSCRIPTION_PLANS_PATH', '/plans'),

];
