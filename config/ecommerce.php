<?php

return [
    'base_domain' => env('ECOMMERCE_BASE_DOMAIN', 'omnisolution.shop'),

    /*
    | Signalements boutique vitrine (modération root)
    */
    'shop_reports' => [
        /** Nombre de signalements en attente avant alerte « élevé » dans l’admin */
        'threshold_warning' => (int) env('ECOMMERCE_REPORT_THRESHOLD_WARNING', 5),
        /** Nombre de signalements en attente avant alerte « critique » */
        'threshold_critical' => (int) env('ECOMMERCE_REPORT_THRESHOLD_CRITICAL', 10),
        /** Max signalements par IP et par boutique sur 24 h */
        'rate_limit_per_shop_ip_per_day' => (int) env('ECOMMERCE_REPORT_RATE_LIMIT', 3),
    ],
];
