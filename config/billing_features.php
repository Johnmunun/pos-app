<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Catalog
    |--------------------------------------------------------------------------
    |
    | Central catalog for SaaS plan capabilities.
    | This file is intentionally read-only for runtime behavior for now.
    | It helps standardize feature codes and default plan templates.
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Plan Templates
    |--------------------------------------------------------------------------
    |
    | Templates are not force-applied automatically.
    | They can be used by admin tooling/seeding and future sync commands.
    |
    */
    'plan_templates' => [
        'starter' => [
            'products.max' => ['label' => 'Produits max', 'enabled' => true, 'limit' => 300],
            'users.max' => ['label' => 'Utilisateurs max', 'enabled' => true, 'limit' => 3],
            'api.payments' => ['label' => 'API paiements', 'enabled' => false, 'limit' => null],
            'analytics.advanced' => ['label' => 'Analytics avancees', 'enabled' => false, 'limit' => null],
            'reports.export.csv' => ['label' => 'Export CSV', 'enabled' => true, 'limit' => null],
            'reports.export.pdf' => ['label' => 'Export PDF', 'enabled' => false, 'limit' => null],
            'reports.export.excel' => ['label' => 'Export Excel', 'enabled' => false, 'limit' => null],
        ],
        'pro' => [
            'products.max' => ['label' => 'Produits max', 'enabled' => true, 'limit' => 3000],
            'users.max' => ['label' => 'Utilisateurs max', 'enabled' => true, 'limit' => 20],
            'api.payments' => ['label' => 'API paiements', 'enabled' => true, 'limit' => null],
            'analytics.advanced' => ['label' => 'Analytics avancees', 'enabled' => true, 'limit' => null],
            'reports.export.csv' => ['label' => 'Export CSV', 'enabled' => true, 'limit' => null],
            'reports.export.pdf' => ['label' => 'Export PDF', 'enabled' => true, 'limit' => null],
            'reports.export.excel' => ['label' => 'Export Excel', 'enabled' => true, 'limit' => null],
        ],
        'enterprise' => [
            'products.max' => ['label' => 'Produits max', 'enabled' => true, 'limit' => null],
            'users.max' => ['label' => 'Utilisateurs max', 'enabled' => true, 'limit' => null],
            'api.payments' => ['label' => 'API paiements', 'enabled' => true, 'limit' => null],
            'analytics.advanced' => ['label' => 'Analytics avancees', 'enabled' => true, 'limit' => null],
            'reports.export.csv' => ['label' => 'Export CSV', 'enabled' => true, 'limit' => null],
            'reports.export.pdf' => ['label' => 'Export PDF', 'enabled' => true, 'limit' => null],
            'reports.export.excel' => ['label' => 'Export Excel', 'enabled' => true, 'limit' => null],
        ],
    ],
];
