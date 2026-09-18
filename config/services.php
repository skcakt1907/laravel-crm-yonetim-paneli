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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'resellerclub' => [
        'user_id' => env('RESELLERCLUB_USER_ID'),
        'api_key' => env('RESELLERCLUB_API_KEY'),
        'enabled' => env('RESELLERCLUB_ENABLED', false),
        'default_nameservers' => env('RESELLERCLUB_DEFAULT_NAMESERVERS', ''),
    ],
    
    'paytr' => [
        'merchant_id' => env('PAYTR_MERCHANT_ID'),
        'merchant_key' => env('PAYTR_MERCHANT_KEY'),
        'merchant_salt' => env('PAYTR_MERCHANT_SALT'),
        'test_mode' => env('PAYTR_TEST_MODE', true),
    ],
    
    'iyzico' => [
        'api_key' => env('IYZICO_API_KEY'),
        'secret_key' => env('IYZICO_SECRET_KEY'),
        'base_url' => env('IYZICO_BASE_URL', 'https://api.iyzipay.com'),
        'test_mode' => env('IYZICO_TEST_MODE', true),
    ],

    'google_translate' => [
        'api_key' => env('GOOGLE_CLOUD_TRANSLATE_KEY'),
    ],

    'netgsm' => [
        'usercode' => env('NETGSM_USERCODE'),
        'password' => env('NETGSM_PASSWORD'),
        'msgheader' => env('NETGSM_MSGHEADER'),
        'api_url' => env('NETGSM_API_URL', 'https://api.netgsm.com.tr/sms/send/get'),
        'timeout' => env('NETGSM_TIMEOUT', 30),
        'enabled' => env('NETGSM_ENABLED', true),
    ],
    
    'metunic' => [
    'url'      => env('METUNIC_API_URL', 'https://api-test.metunic.com.tr/v1'),
    'username' => env('info@ornek.com'),
    'password' => env('Dngrup123.'),
    ],

];
