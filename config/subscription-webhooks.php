<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Webhook Routes
    |--------------------------------------------------------------------------
    |
    | Configure webhook route handling
    |
    */
    'routes' => [
        // Enable automatic route registration
        'enabled' => env('SUBSCRIPTION_WEBHOOKS_ROUTES_ENABLED', false),
        
        // Middleware to apply to webhook routes
        'middleware' => ['api'],
        
        // Prefix for webhook routes
        'prefix' => 'webhooks/subscriptions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Platforms
    |--------------------------------------------------------------------------
    |
    | Configuration for different subscription platforms
    |
    */
    'platforms' => [
        'apple' => [
            // Apple-specific configuration
            'verify_signature' => env('APPLE_WEBHOOK_VERIFY_SIGNATURE', true),
            'public_key_path' => storage_path('apple/public_key.pem'),
        ],
        
        'google' => [
            // Google-specific configuration
            'verify_signature' => env('GOOGLE_WEBHOOK_VERIFY_SIGNATURE', true),
            'service_account_path' => storage_path('google/service_account.json'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Processing
    |--------------------------------------------------------------------------
    |
    | Configure how webhooks are processed
    |
    */
    'processing' => [
        // Queue webhook processing
        'queue' => env('SUBSCRIPTION_WEBHOOKS_QUEUE', false),
        
        // Default queue connection
        'queue_connection' => env('SUBSCRIPTION_WEBHOOKS_QUEUE_CONNECTION', 'default'),
    ],
];