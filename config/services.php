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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Equipment API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the external equipment API service that provides
    | equipment master data and running time information.
    |
    */

    'equipment_api' => [
        'url' => env('EQUIPMENT_API_URL', 'https://api.example.com/v1'),
        'token' => env('EQUIPMENT_API_TOKEN'),
        'timeout' => env('EQUIPMENT_API_TIMEOUT', 120),
        'retry_attempts' => env('EQUIPMENT_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('EQUIPMENT_API_RETRY_DELAY', 1000), // milliseconds
    ],

];
