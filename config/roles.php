<?php

/**
 * Définition des rôles et permissions du système POS SaaS
 */

return [
    'roles' => [
        'ROOT' => [
            'name' => 'Administrateur Principal',
            'description' => 'Accès complet à tous les tenants et gestion globale',
            'permissions' => [
                'manage.tenants',
                'manage.users',
                'view.analytics',
                'manage.settings',
            ],
        ],
        'TENANT_ADMIN' => [
            'name' => 'Administrateur Tenant',
            'description' => 'Admin d\'un tenant spécifique',
            'permissions' => [
                'manage.tenant.users',
                'manage.tenant.products',
                'manage.tenant.sales',
                'view.tenant.analytics',
                'manage.tenant.settings',
            ],
        ],
        'MERCHANT' => [
            'name' => 'Commerçant',
            'description' => 'Utilisateur standard du tenant',
            'permissions' => [
                'view.products',
                'create.sales',
                'view.sales',
                'view.profile',
            ],
        ],
        'SELLER' => [
            'name' => 'Vendeur',
            'description' => 'Vendeur avec droits limités',
            'permissions' => [
                'create.sales',
                'view.sales',
                'view.profile',
            ],
        ],
        'STAFF' => [
            'name' => 'Personnel',
            'description' => 'Personnel avec droits minimaux',
            'permissions' => [
                'view.sales',
                'view.profile',
            ],
        ],
    ],

    'permissions' => [
        // Gestion des tenants (ROOT uniquement)
        'manage.tenants' => 'Créer, modifier, supprimer des tenants',
        'manage.users' => 'Gérer les utilisateurs globalement',
        'view.analytics' => 'Voir les analytics globales',
        'manage.settings' => 'Gérer les paramètres globaux',

        // Gestion tenant (TENANT_ADMIN)
        'manage.tenant.users' => 'Gérer les utilisateurs du tenant',
        'manage.tenant.products' => 'Gérer les produits du tenant',
        'manage.tenant.sales' => 'Gérer les ventes du tenant',
        'view.tenant.analytics' => 'Voir les analytics du tenant',
        'manage.tenant.settings' => 'Gérer les paramètres du tenant',

        // Opérations standard
        'view.products' => 'Voir les produits',
        'create.sales' => 'Créer une vente',
        'view.sales' => 'Voir les ventes',
        'view.profile' => 'Voir son profil',
    ],
];
