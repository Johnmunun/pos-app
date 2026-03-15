<?php

return [
    'pharmacy' => [
        'steps' => [
            [
                'id' => 'pharmacy-dashboard-welcome',
                'target' => '[data-onboarding="pharmacy-dashboard-welcome"]',
                'title' => 'Bienvenue sur le module Pharmacie',
                'content' => 'Ce tableau de bord vous donne une vue d\'ensemble des ventes, du stock et des indicateurs clés. Utilisez les cartes ci-dessous pour accéder rapidement aux différentes sections.',
                'order' => 1,
            ],
            [
                'id' => 'pharmacy-sidebar-nav',
                'target' => '[data-onboarding="module-sidebar-nav"]',
                'title' => 'Menu de navigation',
                'content' => 'Le menu latéral vous permet d\'accéder à toutes les sections : Dashboard (vue d\'ensemble), Produits et Catégories (catalogue), Stock et Inventaires (niveaux et mouvements), Ventes (caisse et historique), Achats et Fournisseurs, Clients et Vendeurs, Transferts et Rapports. Cliquez sur un lien pour changer de page.',
                'order' => 2,
            ],
            [
                'id' => 'pharmacy-quick-actions',
                'target' => '[data-onboarding="pharmacy-quick-actions"]',
                'title' => 'Actions rapides',
                'content' => 'Accédez rapidement à une nouvelle vente, aux produits, au stock et aux rapports. Ces raccourcis vous font gagner du temps au quotidien.',
                'order' => 3,
            ],
            [
                'id' => 'pharmacy-sales-card',
                'target' => '[data-onboarding="pharmacy-sales-card"]',
                'title' => 'Ventes du jour',
                'content' => 'Consultez ici le chiffre d\'affaires et le nombre de ventes validées aujourd\'hui.',
                'order' => 4,
            ],
            [
                'id' => 'pharmacy-stock-value',
                'target' => '[data-onboarding="pharmacy-stock-value"]',
                'title' => 'Valeur du stock',
                'content' => 'La valeur totale de votre stock actuel est affichée ici. Elle est mise à jour en fonction des mouvements et des prix.',
                'order' => 5,
            ],
        ],
    ],
    'commerce' => [
        'steps' => [
            [
                'id' => 'commerce-sidebar-nav',
                'target' => '[data-onboarding="module-sidebar-nav"]',
                'title' => 'Menu de navigation',
                'content' => 'Le menu latéral regroupe les sections du module Commerce : Dashboard (indicateurs), Ventes (caisse et historique), Produits et Catégories, Stock et Inventaires, Fournisseurs, Clients, Transferts et Rapports. Utilisez-le pour naviguer dans l\'application.',
                'order' => 1,
            ],
        ],
    ],
    'hardware' => [
        'steps' => [
            [
                'id' => 'hardware-dashboard-welcome',
                'target' => '[data-onboarding="hardware-dashboard-welcome"]',
                'title' => 'Bienvenue sur le module Quincaillerie',
                'content' => 'Ce tableau de bord vous donne une vue d\'ensemble des ventes, du stock et des indicateurs clés du module Quincaillerie. Utilisez les cartes ci-dessous pour accéder rapidement aux différentes sections.',
                'order' => 1,
            ],
            [
                'id' => 'hardware-sidebar-nav',
                'target' => '[data-onboarding="module-sidebar-nav"]',
                'title' => 'Menu de navigation',
                'content' => 'Le menu latéral vous permet d\'accéder à toutes les sections : Dashboard (vue d\'ensemble), Produits et Catégories (catalogue), Stock et Mouvements, Ventes (caisse et historique), Bons de commande et Fournisseurs, Clients, Transferts et Rapports. Cliquez sur un lien pour changer de page.',
                'order' => 2,
            ],
            [
                'id' => 'hardware-quick-actions',
                'target' => '[data-onboarding="hardware-quick-actions"]',
                'title' => 'Actions rapides',
                'content' => 'Accédez rapidement à une nouvelle vente, aux produits, au stock, aux rapports et aux bons de commande. Ces raccourcis vous font gagner du temps au quotidien.',
                'order' => 3,
            ],
            [
                'id' => 'hardware-sales-card',
                'target' => '[data-onboarding="hardware-sales-card"]',
                'title' => 'Ventes du jour',
                'content' => 'Consultez ici le chiffre d\'affaires et le nombre de ventes validées aujourd\'hui.',
                'order' => 4,
            ],
            [
                'id' => 'hardware-stock-value',
                'target' => '[data-onboarding="hardware-stock-value"]',
                'title' => 'Valeur du stock',
                'content' => 'La valeur totale de votre stock actuel est affichée ici. Elle est mise à jour en fonction des mouvements et des prix.',
                'order' => 5,
            ],
        ],
    ],
    'ecommerce' => [
        'steps' => [
            [
                'id' => 'ecommerce-dashboard-welcome',
                'target' => '[data-onboarding="ecommerce-dashboard-welcome"]',
                'title' => 'Bienvenue sur le module E-commerce',
                'content' => 'Ce tableau de bord vous donne une vue d\'ensemble des commandes, des clients et des revenus de votre boutique en ligne. Utilisez les cartes et le menu latéral pour naviguer.',
                'order' => 1,
            ],
            [
                'id' => 'ecommerce-sidebar-nav',
                'target' => '[data-onboarding="module-sidebar-nav"]',
                'title' => 'Menu de navigation',
                'content' => 'Le menu latéral regroupe toutes les sections E-commerce : Dashboard, Catalogue et Produits, Catégories, Ventes (commandes), Clients, Fournisseurs, Paiements, Livraisons, Promotions, Coupons, Avis, Stock, Rapports, Paramètres, Marketing, et le CMS (pages, bannières, blog, médias). Utilisez-le pour gérer votre boutique.',
                'order' => 2,
            ],
            [
                'id' => 'ecommerce-orders-card',
                'target' => '[data-onboarding="ecommerce-orders-card"]',
                'title' => 'Total commandes',
                'content' => 'Consultez ici le nombre total de commandes, les commandes du jour et celles en attente.',
                'order' => 3,
            ],
            [
                'id' => 'ecommerce-revenue-card',
                'target' => '[data-onboarding="ecommerce-revenue-card"]',
                'title' => 'Revenus',
                'content' => 'Les revenus du jour et sur les 7 derniers jours (commandes payées) sont affichés dans les cartes ci-dessous.',
                'order' => 4,
            ],
        ],
    ],
];
