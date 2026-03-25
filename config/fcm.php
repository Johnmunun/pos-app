<?php

return [
    'enabled' => (bool) env('FCM_ENABLED', false),

    // Firebase project settings
    'project_id' => env('FCM_PROJECT_ID'),

    // Public web config (safe to expose to browser + service worker)
    'web' => [
        'api_key' => env('FCM_WEB_API_KEY'),
        'auth_domain' => env('FCM_WEB_AUTH_DOMAIN'),
        'project_id' => env('FCM_WEB_PROJECT_ID', env('FCM_PROJECT_ID')),
        'storage_bucket' => env('FCM_WEB_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FCM_WEB_MESSAGING_SENDER_ID'),
        'app_id' => env('FCM_WEB_APP_ID'),
    ],

    // Web Push (VAPID) public key for getToken()
    'vapid_public_key' => env('FCM_VAPID_PUBLIC_KEY'),

    // Service account JSON (HTTP v1). Provide either JSON content or a file path.
    'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
    'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),

    'default' => [
        'icon_url' => env('FCM_DEFAULT_ICON_URL', '/icons/icon-192x192.png'),
        'click_url' => env('FCM_DEFAULT_CLICK_URL', '/dashboard'),
    ],
];

