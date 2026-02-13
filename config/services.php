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
    
    'dbox' => [
        'base_url' => env('DBOX_BASE_URL', ''),
        'mkey'     => env('DBOX_MKEY', ''),
        'mscrt'    => env('DBOX_MSCRT', ''),
        'timeout'  => env('DBOX_TIMEOUT', 20),
    
        // ✅ add this
        'game_wallet_type' => env('GAME_WALLET_TYPE', 'chips'),
    
        'wallet_type_param' => env('DBOX_WALLET_TYPE_PARAM', 'merWltType'),
        'wallet_type_map' => [
            'chips' => env('DBOX_WALLET_TYPE_CHIPS', 'CHIP'),
            'bonus' => env('DBOX_WALLET_TYPE_BONUS', 'BONUS'),
        ],
    ],
    
    'vpay' => [
        'gateway'     => env('VPAY_GATEWAY', 'https://gateway.vpay.club'),
        'token'       => env('VPAY_TOKEN'),
        'trader_id'   => env('VPAY_TRADER_ID'),
        'notify_url'  => env('VPAY_NOTIFY_URL'),
        'callback_url'=> env('VPAY_CALLBACK_URL'),
        'callback_ip' => env('VPAY_CALLBACK_IP', '43.217.190.244'),
    ],
    
    'winpay' => [
        'base_url'    => env('WINPAY_BASE_URL', ''),   // https://winpay.cash
        'pay_path'    => env('WINPAY_PAY_PATH', ''),   // example: /api/v3/deposit  (or the exact pay endpoint they require)
        'query_path'  => env('WINPAY_QUERY_PATH', ''), // example: /api/v3/deposit/query
        'client_id'   => env('WINPAY_CLIENT_ID', ''),  // 商户号 / client_id
        'api_key'     => env('WINPAY_API_KEY', ''),    // APIKEY
        'notify_url'  => env('WINPAY_NOTIFY_URL', ''),
        'return_url'  => env('WINPAY_RETURN_URL', ''),
        'timeout'     => (int) env('WINPAY_TIMEOUT', 20),
        'debug'       => (bool) env('WINPAY_DEBUG', false),
        'log_channel' => env('WINPAY_LOG_CHANNEL', 'winpay_daily'),
        'sign_delimiter' => env('WINPAY_SIGN_DELIMITER', '&'),
        'callback_ip' => env('WINPAY_CALLBACK_IP', '43.128.238.109'),
    ],


];
