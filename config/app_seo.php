<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEO site marketing (domaine principal, ex. omnisolution.shop)
    |--------------------------------------------------------------------------
    | La vitrine e-commerce (sous-domaines) utilise Marketing & SEO par boutique.
    | Ces valeurs s’appliquent à la landing, login, onboarding.
    | Un fichier settings/app/seo.json (admin Branding) peut surcharger la config.
    */

    'site_name' => env('APP_SEO_SITE_NAME', env('APP_NAME', 'OmniSolution')),

    'title' => env('APP_SEO_TITLE', 'OmniSolution — Logiciel de caisse, stock et e-commerce'),

    'description' => env('APP_SEO_DESCRIPTION', 'Gérez vos ventes, stocks, pharmacie, quincaillerie ou commerce avec OmniSolution : POS, rapports, paiements mobiles et boutique en ligne.'),

    'keywords' => env('APP_SEO_KEYWORDS', 'logiciel caisse, POS, gestion stock, e-commerce, pharmacie, quincaillerie, RDC, Afrique'),

    'indexing_enabled' => env('APP_SEO_INDEXING_ENABLED', true),

    'google_site_verification' => env('APP_GOOGLE_SITE_VERIFICATION', ''),

    'og_image' => env('APP_SEO_OG_IMAGE', null),

    'twitter_handle' => env('APP_SEO_TWITTER', null),

    'locale' => env('APP_SEO_LOCALE', 'fr_FR'),

];
