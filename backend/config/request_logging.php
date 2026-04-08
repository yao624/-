<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Request Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the request logging
    | middleware. You can customize which requests should be logged and
    | which sensitive data should be filtered.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Skip Paths
    |--------------------------------------------------------------------------
    |
    | These paths will be excluded from request logging. You can use wildcards
    | with asterisks (*) to match multiple paths.
    |
    */
    'skip_paths' => [
        'health',
        'health/*',
        'up',
        'status',
        'ping',
        'metrics',
        'favicon.ico',
        '_debugbar/*',
        'telescope/*',
        'horizon/*',
        'api/documentation',
        'docs/*',
        'swagger/*',
        'api/v2/download',
        'api/v2/request-logs',
        'api/v2/request-logs/*'
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Methods
    |--------------------------------------------------------------------------
    |
    | These HTTP methods will be excluded from request logging.
    |
    */
    'skip_methods' => [
        'OPTIONS',
        'HEAD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | These fields will be filtered out from the request body before logging
    | to prevent sensitive information from being stored.
    |
    */
    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'private_key',
        'client_secret',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security_number',
        'bank_account',
        'routing_number',
        'pin',
        'otp',
        'verification_code',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Set this to false to completely disable request logging.
    |
    */
    'enabled' => env('REQUEST_LOGGING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Only Authenticated Requests
    |--------------------------------------------------------------------------
    |
    | Set this to true to only log requests from authenticated users.
    |
    */
    'authenticated_only' => env('REQUEST_LOGGING_AUTHENTICATED_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Maximum Request Body Size
    |--------------------------------------------------------------------------
    |
    | Maximum size of request body to log (in bytes). Larger requests will
    | have their body truncated or skipped entirely.
    |
    */
    'max_body_size' => env('REQUEST_LOGGING_MAX_BODY_SIZE', 10240), // 10KB

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep request logs. Older logs will be automatically
    | deleted by a scheduled job.
    |
    */
    'retention_days' => env('REQUEST_LOGGING_RETENTION_DAYS', 30),
];
