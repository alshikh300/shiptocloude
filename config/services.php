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
    'sms' => [
        'endpoint' => env('SMS_API_ENDPOINT'),
        'inst_id' => env('SMS_INST_ID'),

    ],
    'ykb_api' => [
        'client_id' => env('YKB_API_CLIENT_ID'),
        'client_secret' => env('YKB_API_CLIENT_SECRET'),
        'token_url' => env('YKB_API_TOKEN_URL'),
        'base_url' => env('YKB_API_BASE_URL'),
        'inst_id' => env('YKB_API_INST_ID'),
    ],
    'mastercard_api' => [
        'base_url' => env('MASTERCARD_API_BASE_URL'),
        'rsa_public_key'   => env('MASTERCARD_ENCRYPTION_RSA_PUBLIC_KEY'),
        'aes_key'          => env('MASTERCARD_AES_KEY'),
        'rsa_fingerprint'  => env('MASTERCARD_RSA_FINGERPRINT'),
        'cert_path' => env('MASTERCARD_CERT_PATH'),
        'cert_password' => env('MASTERCARD_CERT_PASSWORD'),
    ],
    'bas_api' => [
        'client_id' => env('BAS_API_CLIENT_ID'),
        'client_secret' => env('BAS_API_CLIENT_SECRET'),
        'm_key' => env('BAS_API_M_KEY'),
        'm_iv' => env('BAS_API_M_IV'),
        'app_id' => env('BAS_API_APP_ID'),
        'base_url' => env('BAS_API_BASE_URL'),
    ],

];
