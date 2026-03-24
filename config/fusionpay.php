<?php

return [
    'enabled' => env('FUSIONPAY_ENABLED', false),

    // "Lien API" fourni dans le dashboard Money Fusion.
    // S'il est défini, il est prioritaire sur base_url + endpoints.
    'api_link' => env('FUSIONPAY_API_LINK'),

    'base_url' => env('FUSIONPAY_BASE_URL', 'https://api.moneyfusion.net'),
    'verify_base_url' => env('FUSIONPAY_VERIFY_BASE_URL', 'https://www.pay.moneyfusion.net'),
    'api_key' => env('FUSIONPAY_API_KEY'),
    'webhook_secret' => env('FUSIONPAY_WEBHOOK_SECRET'),
    'timeout' => (int) env('FUSIONPAY_TIMEOUT', 20),
    'payin_currency' => env('FUSIONPAY_PAYIN_CURRENCY', 'CDF'),
    'minimum_amount' => (float) env('FUSIONPAY_MINIMUM_AMOUNT', 200),

    // Endpoints personnalisables pour éviter le hardcode.
    'endpoints' => [
        'init_payment' => env('FUSIONPAY_INIT_ENDPOINT', '/payin/init'),
        'verify_payment' => env('FUSIONPAY_VERIFY_ENDPOINT', '/paiementNotif/{token}'),
    ],

    'callback_url' => env('FUSIONPAY_CALLBACK_URL'),
];
