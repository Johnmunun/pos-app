<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Catalog
    |--------------------------------------------------------------------------
    |
    | Source de verite pour les codes de fonctionnalites (plans, overrides, routes).
    | L'admin Billing enregistre les valeurs par plan en base ; les cles doivent exister
    | ici pour etre persistees. Ajouter une entree + l'associer aux routes (middleware
    | feature.enabled:...) ou au FeatureLimitService selon le besoin.
    |
    */
    'catalog' => [
        'products.max' => [
            'label' => 'Produits max',
            'group' => 'catalog',
            'type' => 'limit',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'users.max' => [
            'label' => 'Utilisateurs max',
            'group' => 'access',
            'type' => 'limit',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'sales.monthly.max' => [
            'label' => 'Ventes max (mois en cours)',
            'group' => 'operations',
            'type' => 'limit',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'api.payments' => [
            'label' => 'API paiements',
            'group' => 'integrations',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'analytics.advanced' => [
            'label' => 'Analytics avancees',
            'group' => 'analytics',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'reports.export.csv' => [
            'label' => 'Export CSV',
            'group' => 'reports',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'reports.export.pdf' => [
            'label' => 'Export PDF',
            'group' => 'reports',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'reports.export.excel' => [
            'label' => 'Export Excel',
            'group' => 'reports',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'support.priority' => [
            'label' => 'Support prioritaire',
            'group' => 'support',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ai.assistant' => [
            'label' => 'Assistant IA',
            'group' => 'ai',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ai.product.image.generate' => [
            'label' => 'IA: génération image produit (titre + description)',
            'group' => 'ai',
            'type' => 'limit',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ai.media.image.generate' => [
            'label' => 'IA: génération image média CMS',
            'group' => 'ai',
            'type' => 'limit',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ai.product.seo.generate' => [
            'label' => 'IA: génération SEO produit (meta title, meta description, slug)',
            'group' => 'ai',
            'type' => 'limit',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ai.ecommerce.support' => [
            'label' => 'IA: support client e-commerce (livraison, retours, dispo, statut commande)',
            'group' => 'ai',
            'type' => 'limit',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ai.ecommerce.semantic_search' => [
            'label' => 'IA: recherche sémantique produits e-commerce',
            'group' => 'ai',
            'type' => 'limit',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'multi.depot' => [
            'label' => 'Multi-depot',
            'group' => 'operations',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ecommerce.catalog' => [
            'label' => 'E-commerce catalogue',
            'group' => 'ecommerce',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'ecommerce.orders' => [
            'label' => 'E-commerce commandes',
            'group' => 'ecommerce',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'ecommerce.promotions' => [
            'label' => 'E-commerce promotions/coupons',
            'group' => 'ecommerce',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ecommerce.marketing.pro' => [
            'label' => 'E-commerce marketing Pro (pixels, tags, studio IA)',
            'group' => 'ecommerce',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'ecommerce.product.ai_vision' => [
            'label' => 'E-commerce : analyse IA de l\'image produit (titre, description, taille, etc.)',
            'group' => 'ecommerce',
            'type' => 'boolean',
            'default_enabled' => false,
            'default_limit' => null,
        ],
        'pharmacy.module' => [
            'label' => 'Module pharmacie',
            'group' => 'modules',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'commerce.module' => [
            'label' => 'Module global commerce',
            'group' => 'modules',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'hardware.module' => [
            'label' => 'Module quincaillerie',
            'group' => 'modules',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
        'ecommerce.module' => [
            'label' => 'Module e-commerce',
            'group' => 'modules',
            'type' => 'boolean',
            'default_enabled' => true,
            'default_limit' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Plan Templates
    |--------------------------------------------------------------------------
    |
    | Templates are not force-applied automatically.
    | They can be used by admin tooling/seeding and future sync commands.
    |
    | Product ladder (upgrade motivation):
    | - Starter: POS/stock quotidien avec plafonds serres (produits, ventes/mois, utilisateurs) ;
    |   commandes e-com possibles mais comptees dans le plafond ventes ; pas de promo, pas d'API
    |   / analytics avances / IA / multi-depot — le saut vers Pro est clair.
    | - Pro: croissance (commandes web, promos, exports, API, analytics, IA, multi-depots plafonne).
    | - Enterprise: plafonds leves ou illimites + support prioritaire.
    |
    */
    'plan_templates' => [
        'starter' => [
            'products.max' => ['label' => 'Produits max', 'enabled' => true, 'limit' => 200],
            'users.max' => ['label' => 'Utilisateurs max', 'enabled' => true, 'limit' => 2],
            'api.payments' => ['label' => 'API paiements', 'enabled' => false, 'limit' => null],
            'analytics.advanced' => ['label' => 'Analytics avancees', 'enabled' => false, 'limit' => null],
            'reports.export.csv' => ['label' => 'Export CSV', 'enabled' => true, 'limit' => null],
            'reports.export.pdf' => ['label' => 'Export PDF', 'enabled' => false, 'limit' => null],
            'reports.export.excel' => ['label' => 'Export Excel', 'enabled' => false, 'limit' => null],
            'support.priority' => ['label' => 'Support prioritaire', 'enabled' => false, 'limit' => null],
            'ai.assistant' => ['label' => 'Assistant IA', 'enabled' => false, 'limit' => null],
            'ai.product.image.generate' => ['label' => 'IA: génération image produit (titre + description)', 'enabled' => false, 'limit' => 0],
            'ai.media.image.generate' => ['label' => 'IA: génération image média CMS', 'enabled' => false, 'limit' => 0],
            'ai.product.seo.generate' => ['label' => 'IA: génération SEO produit (meta title, meta description, slug)', 'enabled' => false, 'limit' => 0],
            'ai.ecommerce.support' => ['label' => 'IA: support client e-commerce (livraison, retours, dispo, statut commande)', 'enabled' => false, 'limit' => 0],
            'ai.ecommerce.semantic_search' => ['label' => 'IA: recherche sémantique produits e-commerce', 'enabled' => false, 'limit' => 0],
            'multi.depot' => ['label' => 'Multi-depot', 'enabled' => false, 'limit' => null],
            'sales.monthly.max' => ['label' => 'Ventes max (mois en cours)', 'enabled' => true, 'limit' => 100],
            'ecommerce.catalog' => ['label' => 'E-commerce catalogue', 'enabled' => true, 'limit' => null],
            'ecommerce.orders' => ['label' => 'E-commerce commandes', 'enabled' => true, 'limit' => null],
            'ecommerce.promotions' => ['label' => 'E-commerce promotions/coupons', 'enabled' => false, 'limit' => null],
            'ecommerce.marketing.pro' => ['label' => 'E-commerce marketing Pro (pixels, tags, studio IA)', 'enabled' => false, 'limit' => null],
            'ecommerce.product.ai_vision' => ['label' => 'E-commerce : analyse IA de l\'image produit (titre, description, taille, etc.)', 'enabled' => false, 'limit' => null],
            'pharmacy.module' => ['label' => 'Module pharmacie', 'enabled' => true, 'limit' => null],
            'commerce.module' => ['label' => 'Module global commerce', 'enabled' => true, 'limit' => null],
            'hardware.module' => ['label' => 'Module quincaillerie', 'enabled' => true, 'limit' => null],
            'ecommerce.module' => ['label' => 'Module e-commerce', 'enabled' => true, 'limit' => null],
        ],
        'pro' => [
            'products.max' => ['label' => 'Produits max', 'enabled' => true, 'limit' => 5000],
            'users.max' => ['label' => 'Utilisateurs max', 'enabled' => true, 'limit' => 25],
            'api.payments' => ['label' => 'API paiements', 'enabled' => true, 'limit' => null],
            'analytics.advanced' => ['label' => 'Analytics avancees', 'enabled' => true, 'limit' => null],
            'reports.export.csv' => ['label' => 'Export CSV', 'enabled' => true, 'limit' => null],
            'reports.export.pdf' => ['label' => 'Export PDF', 'enabled' => true, 'limit' => null],
            'reports.export.excel' => ['label' => 'Export Excel', 'enabled' => true, 'limit' => null],
            'support.priority' => ['label' => 'Support prioritaire', 'enabled' => false, 'limit' => null],
            'ai.assistant' => ['label' => 'Assistant IA', 'enabled' => true, 'limit' => null],
            'ai.product.image.generate' => ['label' => 'IA: génération image produit (titre + description)', 'enabled' => true, 'limit' => 200],
            'ai.media.image.generate' => ['label' => 'IA: génération image média CMS', 'enabled' => true, 'limit' => 100],
            'ai.product.seo.generate' => ['label' => 'IA: génération SEO produit (meta title, meta description, slug)', 'enabled' => true, 'limit' => 500],
            'ai.ecommerce.support' => ['label' => 'IA: support client e-commerce (livraison, retours, dispo, statut commande)', 'enabled' => true, 'limit' => 1000],
            'ai.ecommerce.semantic_search' => ['label' => 'IA: recherche sémantique produits e-commerce', 'enabled' => true, 'limit' => 1500],
            'multi.depot' => ['label' => 'Multi-depot', 'enabled' => true, 'limit' => 5],
            'sales.monthly.max' => ['label' => 'Ventes max (mois en cours)', 'enabled' => true, 'limit' => 5000],
            'ecommerce.catalog' => ['label' => 'E-commerce catalogue', 'enabled' => true, 'limit' => null],
            'ecommerce.orders' => ['label' => 'E-commerce commandes', 'enabled' => true, 'limit' => null],
            'ecommerce.promotions' => ['label' => 'E-commerce promotions/coupons', 'enabled' => true, 'limit' => null],
            'ecommerce.marketing.pro' => ['label' => 'E-commerce marketing Pro (pixels, tags, studio IA)', 'enabled' => true, 'limit' => null],
            'ecommerce.product.ai_vision' => ['label' => 'E-commerce : analyse IA de l\'image produit (titre, description, taille, etc.)', 'enabled' => true, 'limit' => null],
            'pharmacy.module' => ['label' => 'Module pharmacie', 'enabled' => true, 'limit' => null],
            'commerce.module' => ['label' => 'Module global commerce', 'enabled' => true, 'limit' => null],
            'hardware.module' => ['label' => 'Module quincaillerie', 'enabled' => true, 'limit' => null],
            'ecommerce.module' => ['label' => 'Module e-commerce', 'enabled' => true, 'limit' => null],
        ],
        'enterprise' => [
            'products.max' => ['label' => 'Produits max', 'enabled' => true, 'limit' => null],
            'users.max' => ['label' => 'Utilisateurs max', 'enabled' => true, 'limit' => null],
            'api.payments' => ['label' => 'API paiements', 'enabled' => true, 'limit' => null],
            'analytics.advanced' => ['label' => 'Analytics avancees', 'enabled' => true, 'limit' => null],
            'reports.export.csv' => ['label' => 'Export CSV', 'enabled' => true, 'limit' => null],
            'reports.export.pdf' => ['label' => 'Export PDF', 'enabled' => true, 'limit' => null],
            'reports.export.excel' => ['label' => 'Export Excel', 'enabled' => true, 'limit' => null],
            'support.priority' => ['label' => 'Support prioritaire', 'enabled' => true, 'limit' => null],
            'ai.assistant' => ['label' => 'Assistant IA', 'enabled' => true, 'limit' => null],
            'ai.product.image.generate' => ['label' => 'IA: génération image produit (titre + description)', 'enabled' => true, 'limit' => null],
            'ai.media.image.generate' => ['label' => 'IA: génération image média CMS', 'enabled' => true, 'limit' => null],
            'ai.product.seo.generate' => ['label' => 'IA: génération SEO produit (meta title, meta description, slug)', 'enabled' => true, 'limit' => null],
            'ai.ecommerce.support' => ['label' => 'IA: support client e-commerce (livraison, retours, dispo, statut commande)', 'enabled' => true, 'limit' => null],
            'ai.ecommerce.semantic_search' => ['label' => 'IA: recherche sémantique produits e-commerce', 'enabled' => true, 'limit' => null],
            'multi.depot' => ['label' => 'Multi-depot', 'enabled' => true, 'limit' => null],
            'sales.monthly.max' => ['label' => 'Ventes max (mois en cours)', 'enabled' => true, 'limit' => null],
            'ecommerce.catalog' => ['label' => 'E-commerce catalogue', 'enabled' => true, 'limit' => null],
            'ecommerce.orders' => ['label' => 'E-commerce commandes', 'enabled' => true, 'limit' => null],
            'ecommerce.promotions' => ['label' => 'E-commerce promotions/coupons', 'enabled' => true, 'limit' => null],
            'ecommerce.marketing.pro' => ['label' => 'E-commerce marketing Pro (pixels, tags, studio IA)', 'enabled' => true, 'limit' => null],
            'ecommerce.product.ai_vision' => ['label' => 'E-commerce : analyse IA de l\'image produit (titre, description, taille, etc.)', 'enabled' => true, 'limit' => null],
            'pharmacy.module' => ['label' => 'Module pharmacie', 'enabled' => true, 'limit' => null],
            'commerce.module' => ['label' => 'Module global commerce', 'enabled' => true, 'limit' => null],
            'hardware.module' => ['label' => 'Module quincaillerie', 'enabled' => true, 'limit' => null],
            'ecommerce.module' => ['label' => 'Module e-commerce', 'enabled' => true, 'limit' => null],
        ],
    ],
];
