<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Taux indicatifs par défaut (modifiables ensuite dans l’app).
    | USD = référence 1. Taux = combien d’unités de la devise cible pour 1 USD.
    |--------------------------------------------------------------------------
    */
    'default_usd_to_cdf' => env('STORE_TEMPLATE_USD_TO_CDF', 2850),
    'default_usd_to_xaf' => env('STORE_TEMPLATE_USD_TO_XAF', 620),

    'template_version' => 'v1',
];
