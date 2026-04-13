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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'webpush' => [
        'vapid' => [
            'public_key' => env('VAPID_PUBLIC_KEY'),
            'private_key' => env('VAPID_PRIVATE_KEY'),
            'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce storefront domain (production)
    |--------------------------------------------------------------------------
    | Domaine de base pour les boutiques en ligne (sous-domaines).
    | Ex: ECOMmerce_BASE_DOMAIN=omnisolution.shop => kasashop.omnisolution.shop
    */
    'ecommerce' => [
        'base_domain' => env('ECOMmerce_BASE_DOMAIN', 'omnisolution.shop'),
    ],

    /*
    | Pixel Meta pour l’app Inertia principale (pub / remarketing), distinct du pixel configuré par boutique (vitrine).
    */
    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
    ],

];
