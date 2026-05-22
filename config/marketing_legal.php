<?php

return [

    'company_name' => env('APP_LEGAL_COMPANY_NAME', env('APP_SEO_SITE_NAME', 'OmniSolution')),
    'contact_email' => env('APP_LEGAL_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'contact@omnisolution.shop')),
    'country' => env('APP_LEGAL_COUNTRY', 'République Démocratique du Congo'),
    'last_updated' => '2026-05-21',

    'pages' => [
        'about' => [
            'slug' => 'a-propos',
            'title' => 'À propos de nous',
            'meta_description' => 'Découvrez OmniSolution : logiciel de caisse, gestion de stock et e-commerce pour commerçants en Afrique.',
            'sections' => [
                [
                    'heading' => 'Notre mission',
                    'paragraphs' => [
                        'OmniSolution aide les commerçants, pharmacies, quincailleries et boutiques en ligne à centraliser leurs ventes, leur stock et leur relation client sur une seule plateforme web.',
                        'Nous concevons des outils simples, adaptés aux réalités du terrain : multi-devises, paiements mobiles, équipes multi-utilisateurs et boutiques vitrine par sous-domaine.',
                    ],
                ],
                [
                    'heading' => 'Ce que nous proposons',
                    'paragraphs' => [
                        'Point de vente (POS), gestion des produits et catégories, achats et fournisseurs, rapports, modules sectoriels (pharmacie, quincaillerie, commerce) et module e-commerce avec vitrine publique.',
                        'La plateforme évolue en continu grâce aux retours de nos clients et à un accompagnement support intégré.',
                    ],
                ],
                [
                    'heading' => 'Nous contacter',
                    'paragraphs' => [
                        'Pour une démonstration, un partenariat ou une question commerciale, utilisez la section contact en bas de la page d\'accueil ou écrivez-nous à l\'adresse indiquée dans les mentions légales.',
                    ],
                ],
            ],
        ],
        'privacy' => [
            'slug' => 'politique-de-confidentialite',
            'title' => 'Politique de confidentialité',
            'meta_description' => 'Comment OmniSolution collecte, utilise et protège vos données personnelles.',
            'sections' => [
                [
                    'heading' => '1. Responsable du traitement',
                    'paragraphs' => [
                        'Le responsable du traitement des données est l\'éditeur du service OmniSolution, joignable via l\'adresse e-mail de contact publiée sur ce site.',
                    ],
                ],
                [
                    'heading' => '2. Données collectées',
                    'paragraphs' => [
                        'Nous pouvons traiter : identité et coordonnées (nom, e-mail, téléphone), données de compte et d\'authentification, informations liées à votre commerce (produits, ventes, clients), données techniques (logs, adresse IP, navigateur) et échanges avec le support.',
                        'Les données saisies dans votre espace marchand restent sous votre responsabilité au regard de vos propres clients.',
                    ],
                ],
                [
                    'heading' => '3. Finalités',
                    'paragraphs' => [
                        'Fourniture et amélioration du service, facturation et abonnements, sécurité, support client, obligations légales et — avec votre consentement le cas échéant — communications commerciales.',
                    ],
                ],
                [
                    'heading' => '4. Base légale & conservation',
                    'paragraphs' => [
                        'Les traitements reposent sur l\'exécution du contrat, l\'intérêt légitime (sécurité, amélioration produit) ou votre consentement. Les données sont conservées pendant la durée du compte puis archivées selon les délais légaux applicables.',
                    ],
                ],
                [
                    'heading' => '5. Vos droits',
                    'paragraphs' => [
                        'Vous pouvez demander l\'accès, la rectification, l\'effacement, la limitation ou la portabilité de vos données, ainsi que vous opposer à certains traitements. Contactez-nous par e-mail ; vous pouvez aussi introduire une réclamation auprès de l\'autorité de protection des données compétente.',
                    ],
                ],
                [
                    'heading' => '6. Sécurité & sous-traitants',
                    'paragraphs' => [
                        'Nous mettons en œuvre des mesures techniques et organisationnelles adaptées (accès restreints, HTTPS, sauvegardes). Certains prestataires (hébergement, e-mail, paiement) peuvent traiter des données pour notre compte, dans le respect de contrats de confidentialité.',
                    ],
                ],
            ],
        ],
        'terms' => [
            'slug' => 'conditions-utilisation',
            'title' => 'Conditions d\'utilisation',
            'meta_description' => 'Conditions générales d\'utilisation du service SaaS OmniSolution.',
            'sections' => [
                [
                    'heading' => '1. Objet',
                    'paragraphs' => [
                        'Les présentes conditions régissent l\'accès et l\'utilisation de la plateforme OmniSolution (site, application et API associées). En créant un compte, vous les acceptez sans réserve.',
                    ],
                ],
                [
                    'heading' => '2. Compte & accès',
                    'paragraphs' => [
                        'Vous êtes responsable de la confidentialité de vos identifiants et de l\'activité réalisée sous votre compte. Vous vous engagez à fournir des informations exactes et à notifier toute utilisation non autorisée.',
                    ],
                ],
                [
                    'heading' => '3. Abonnements & essai',
                    'paragraphs' => [
                        'Les formules, tarifs et périodes d\'essai sont décrits sur le site. Les paiements récurrents ou ponctuels sont dus selon le plan choisi. En cas de non-paiement, l\'accès peut être suspendu après notification.',
                    ],
                ],
                [
                    'heading' => '4. Usage acceptable',
                    'paragraphs' => [
                        'Il est interdit d\'utiliser le service à des fins illicites, de tenter d\'accéder à des systèmes tiers, de surcharger l\'infrastructure ou de porter atteinte aux droits de tiers. Nous pouvons suspendre un compte en cas de violation grave.',
                    ],
                ],
                [
                    'heading' => '5. Propriété intellectuelle',
                    'paragraphs' => [
                        'OmniSolution, son code, sa marque et ses interfaces restent notre propriété. Vous conservez la propriété de vos données commerciales. Vous nous accordez une licence limitée pour héberger et traiter ces données afin de fournir le service.',
                    ],
                ],
                [
                    'heading' => '6. Limitation de responsabilité',
                    'paragraphs' => [
                        'Le service est fourni « en l\'état ». Nous nous efforçons d\'assurer disponibilité et sécurité, sans garantie de résultat commercial. Notre responsabilité est limitée au montant des sommes versées sur les douze derniers mois, sauf faute lourde ou disposition impérative contraire.',
                    ],
                ],
                [
                    'heading' => '7. Résiliation & droit applicable',
                    'paragraphs' => [
                        'Vous pouvez résilier votre compte selon les modalités prévues dans l\'espace client. Nous pouvons résilier en cas de manquement après mise en demeure. Le droit applicable et les tribunaux compétents sont ceux du pays d\'établissement de l\'éditeur, sauf dispositions impératives locales.',
                    ],
                ],
            ],
        ],
        'legal' => [
            'slug' => 'mentions-legales',
            'title' => 'Mentions légales',
            'meta_description' => 'Informations légales et éditeur du site OmniSolution.',
            'sections' => [
                [
                    'heading' => 'Éditeur du site',
                    'paragraphs' => [
                        'Raison sociale : configurée par l\'exploitant du service (voir contact ci-dessous).',
                        'Pays d\'exploitation principal : selon configuration de l\'éditeur.',
                    ],
                ],
                [
                    'heading' => 'Contact',
                    'paragraphs' => [
                        'Pour toute demande relative au site ou au service, contactez-nous par e-mail.',
                    ],
                ],
                [
                    'heading' => 'Hébergement',
                    'paragraphs' => [
                        'Le site et l\'application sont hébergés sur des serveurs sécurisés dont l\'identité de l\'hébergeur peut être communiquée sur demande.',
                    ],
                ],
                [
                    'heading' => 'Propriété intellectuelle',
                    'paragraphs' => [
                        'L\'ensemble des éléments du site (textes, graphismes, logo, structure) est protégé. Toute reproduction non autorisée est interdite.',
                    ],
                ],
            ],
        ],
    ],

];
