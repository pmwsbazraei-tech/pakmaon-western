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

    // Tempahan online: Bayar Online
    'payment_driver' => env('PAYMENT_DRIVER', 'fake'),

    'chip' => [
        'brand_id' => env('CHIP_BRAND_ID'),
        'secret_key' => env('CHIP_SECRET_KEY'),
        // Kunci awam webhook (PEM) dari portal CHIP. Kunci success_callback diambil automatik dari /public_key/.
        'webhook_public_key' => env('CHIP_WEBHOOK_PUBLIC_KEY') ? str_replace('\n', "\n", env('CHIP_WEBHOOK_PUBLIC_KEY')) : null,
    ],

];
