<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook API Key
    |--------------------------------------------------------------------------
    |
    | This API key is used to authenticate incoming webhook requests for sync
    | operations. It should be a strong, random string stored in your .env file.
    |
    | Generate a secure key using: php artisan tinker
    | Then run: Str::random(64)
    |
    */

    'api_key' => env('WEBHOOK_API_KEY'),

];
