<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'unisms' => [
        // Secret key from your UniSMS dashboard.
        'secret_key' => env('UNISMS_SECRET_KEY'),

        // Your registered sender ID from the UniSMS dashboard (required by the API).
        'sender_id'  => env('UNISMS_SENDER_ID'),

        // Optional. Used to retry numbers the main sender ID can't reach
        // (e.g. Smart / TNT / Sun). Use an all-network sender ID here.
        'fallback_sender_id' => env('UNISMS_FALLBACK_SENDER_ID'),

        // Base URL of the UniSMS API (the service appends /sms).
        'base_url'   => env('UNISMS_BASE_URL', 'https://unismsapi.com/api'),
    ],
];