# 📘 POS SaaS - Documentation Complète

## Vue d'ensemble

**POS SaaS** est un système de point de vente professionnel, multi-tenant, destiné au marché africain avec architecture **Domain Driven Design (DDD)**.

### Caractéristiques principales

- ✅ **Multi-tenant** : Plusieurs commerçants/boutiques dans une seule application
- ✅ **Rôles et permissions dynamiques** : Aucune permission codée en dur
- ✅ **Architecture DDD** : Séparation claire Domain / Application / Infrastructure
- ✅ **API REST scalable** : Avec Laravel Sanctum
- ✅ **Frontend moderne** : React 18 + Inertia.js + Tailwind CSS
- ✅ **Modèle SaaS** : Système d'abonnement inclus

---

## Structure du projet

```
pos-saas/
├── app/                    # Code Laravel (Controllers, Models, Requests)
├── src/
│   └── Domains/           # Domaines métier (DDD)
│       ├── Tenant/        # Multi-tenancy
│       ├── User/          # Gestion des utilisateurs
│       ├── AccessControl/ # Rôles et permissions
│       ├── Product/       # Catalogue produits
│       ├── Inventory/     # Gestion du stock
│       ├── Sale/          # Ventes
│       ├── Payment/       # Paiements
│       ├── Shop/          # Configuration des boutiques
│       ├── Subscription/  # Plans SaaS
│       └── Reporting/     # Rapports
├── resources/
│   └── js/               # Composants React
├── database/
│   └── migrations/       # Migrations Laravel
├── config/               # Configuration Laravel
├── routes/               # Définition des routes
└── docs/                 # Documentation (ce dossier)
```

---

## Pile technologique

| Couche       | Technologie     | Version |
| ------------ | --------------- | ------- |
| Backend      | Laravel         | 12.x    |
| PHP          | PHP             | 8.2+    |
| Database     | SQLite/MySQL    | -       |
| Frontend     | React           | 18.x    |
| UI Framework | Tailwind CSS    | 3.x     |
| Bridge       | Inertia.js      | 2.x     |
| API Auth     | Laravel Sanctum | 4.x     |
| Bundler      | Vite            | 7.x     |

---

## Principes fondamentaux

### 1. Domain Driven Design (DDD)

Chaque domaine métier est isolé et autonome :

- **Entities** : Objets métier avec identité
- **Value Objects** : Objets sans identité propre
- **Services** : Logique métier complexe
- **Repositories** : Abstraction d'accès aux données
- **Use Cases** : Orchestration des opérations

### 2. Séparation des couches

```
┌─────────────────────────────────────────┐
│           USER INTERFACE (React)        │  Affichage / Interactions
├─────────────────────────────────────────┤
│      APPLICATION (Controllers/Routes)   │  Orchestration des cas d'usage
├─────────────────────────────────────────┤
│      DOMAIN (Use Cases / Services)      │  Logique métier pure (sans Laravel)
├─────────────────────────────────────────┤
│    INFRASTRUCTURE (Laravel / DB)        │  Persistance, ORM, Configuration
└─────────────────────────────────────────┘
```

### 3. Permissions dynamiques

Les permissions ne sont **jamais codées en dur** :

1. Un fichier YAML/TXT définit les permissions
2. Interface admin avec bouton "Générer permissions"
3. Insertion automatique en base de données
4. Aucune permission supprimée automatiquement
5. Les rôles sont créés/modifiés en runtime

### 4. Multi-tenancy

Chaque boutique/commerçant est isolé :

- Les données sont séparées par `tenant_id`
- Middleware pour contextualiser le tenant courant
- Aucune fuite de données entre tenants

---

## Workflow de développement

### Pour chaque domaine, créer :

1. **Entities** - Objets métier
2. **Value Objects** - Objets métier simples
3. **Repositories** - Interfaces d'accès aux données
4. **Services** - Logique métier
5. **Use Cases** - Orchestration
6. **Migrations** - Structure base de données
7. **Models Eloquent** - Implémentation des repositories
8. **Controllers** - Points d'entrée HTTP
9. **Resources** - Sérialization API
10. **Composants React** - Affichage frontend

---

## Documentation par domaine

Voir [DOMAINS.md](./DOMAINS.md) pour le détail de chaque domaine métier.

Voir [ARCHITECTURE.md](./ARCHITECTURE.md) pour les patterns DDD utilisés.

---

## Conventions de nommage

```
Entities:              NomEntity.php
Value Objects:        NomValueObject.php
Services:             NomService.php
Repositories:         NomRepository.php (interface) / EloquentNomRepository.php
Use Cases:            NomUseCase.php
Migrations:           create_nom_table.php
Models:               Nom.php
Controllers:          NomController.php
Requests:             StoreNomRequest.php / UpdateNomRequest.php
Resources:            NomResource.php
Composants React:     NomComponent.jsx
```

---

## Démarrage rapide

```bash
# Installation
composer install
npm install

# Configuration
cp .env.example .env
php artisan key:generate
php artisan migrate

# Développement
npm run dev              # Frontend
php artisan serve        # Backend
```

---

## Prochaines étapes

1. ✅ Créer la structure Tenant domain
2. ⬜ Créer User & AccessControl domains
3. ⬜ Implémenter Product domain
4. ⬜ Implémenter Inventory domain
5. ⬜ Implémenter Sale domain
6. ⬜ Implémenter Payment domain
