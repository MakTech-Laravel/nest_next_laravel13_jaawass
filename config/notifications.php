<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enforce user notification preferences
    |--------------------------------------------------------------------------
    |
    | When true, optional categories (messages, quotes, marketing) respect the
    | boolean flags on the users table. Transactional alerts always deliver.
    |
    */
    'enforce_preferences' => env('NOTIFICATIONS_ENFORCE_PREFERENCES', true),

    /*
    |--------------------------------------------------------------------------
    | Treat unset optional preferences as enabled
    |--------------------------------------------------------------------------
    |
    | User columns default to false in the database. When true, quote/message/
    | supplier toggles only block delivery when explicitly saved as false after
    | the user visits notification settings (updated_at check is not used;
    | instead, false means disabled only for users who saved preferences).
    |
    | For backward compatibility, optional channels default to ALLOW unless
    | enforce_preferences is true AND the specific flag is false — marketing and
    | weekly digest always require true to send.
    */
    'optional_channels_default_enabled' => env('NOTIFICATIONS_OPTIONAL_DEFAULT_ENABLED', true),
];
