<?php

return [

    'base_url' => env('WINPAY_BASE_URL', 'https://winpay.cash'),

    // 文档字段：client_id（商户号）
    'client_id' => env('WINPAY_CLIENT_ID', ''),

    // 文档字段：api_key，用于签名末尾 &key=api_key
    'api_key' => env('WINPAY_API_KEY', ''),

    // 异步通知地址（notify_url）
    'notify_url' => env('WINPAY_NOTIFY_URL', ''),

    // 支付完成跳转地址（return_url）
    'return_url' => env('WINPAY_RETURN_URL', ''),

    // 可选：回调 IP 白名单
    'callback_ip' => env('WINPAY_CALLBACK_IP', ''),

    // 你的这些 endpoint（你给的 v3 URL）
    'endpoints' => [
        'deposit' => '/api/v3/deposit',
        'deposit_query' => '/api/v3/deposit/query',
        'withdraw' => '/api/v3/withdrawals',
        'withdraw_query' => '/api/v3/withdrawals/query',
        'balance' => '/api/v3/balance',
    ],

    // 你当前的业务限制（按你给的）
    'limits' => [
        '01' => ['min' => 100, 'max' => 30000], // FPX Deposit
        '03' => ['min' => 30,  'max' => 5000],  // EWallet Deposit
    ],
];
