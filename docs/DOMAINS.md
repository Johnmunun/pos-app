# 📚 Domaines métier - Documentation

## Vue d'ensemble des domaines

| Domain            | Description                          | Dépendances                      |
| ----------------- | ------------------------------------ | -------------------------------- |
| **Tenant**        | Multi-tenancy, isolation des données | (racine)                         |
| **User**          | Gestion des utilisateurs             | Tenant                           |
| **AccessControl** | Rôles et permissions dynamiques      | User, Tenant                     |
| **Shop**          | Configuration des points de vente    | Tenant                           |
| **Product**       | Catalogue produits                   | Shop, Tenant                     |
| **Inventory**     | Gestion du stock                     | Product, Tenant                  |
| **Sale**          | Transactions de vente                | Product, Inventory, User, Tenant |
| **Payment**       | Traitement des paiements             | Sale, Tenant                     |
| **Subscription**  | Plans SaaS et facturation            | Tenant, User                     |
| **Reporting**     | Rapports et analytics                | Sale, Inventory, Tenant          |

---

## 1️⃣ Tenant Domain

### Objectif

Gérer le multi-tenancy - chaque commerçant/boutique est isolé avec ses propres données.

### Concepts clés

- **Tenant** : Entité principale représentant un commerçant
- **TenantCode** : Identifiant unique du tenant
- **Isolation des données** : Toutes les tables sont filtrées par `tenant_id`

### Structure

```
src/Domains/Tenant/
├── Entities/Tenant.php              # Entity tenant
├── ValueObjects/TenantCode.php       # Code unique
├── ValueObjects/TenantName.php       # Nom du tenant
├── Repositories/TenantRepository.php # Interface repository
├── Services/TenantService.php        # Logique métier
├── Exceptions/                       # TenantNotFoundException
└── UseCases/CreateTenantUseCase.php
```

### Cas d'usage

- Créer un nouveau tenant
- Activer/Désactiver un tenant
- Récupérer infos tenant
- Lister tous les tenants (ROOT seulement)

### Middleware

- `SetCurrentTenant` : Contextualiser le tenant courant à partir du subdomain ou header

---

## 2️⃣ User Domain

### Objectif

Gérer les utilisateurs et leur association aux tenants.

### Concepts clés

- **User** : Entité utilisateur
- **RoleAssignment** : Assignation d'un rôle à un utilisateur (Value Object)
- **Isolation par tenant** : Un utilisateur peut avoir plusieurs rôles dans différents tenants

### Structure

```
src/Domains/User/
├── Entities/User.php
├── ValueObjects/
│   ├── Email.php
│   ├── Password.php
│   └── RoleAssignment.php           # Rôle d'un utilisateur
├── Repositories/UserRepository.php
├── Services/UserService.php
├── Exceptions/
│   ├── UserNotFoundException.php
│   ├── InvalidEmailException.php
│   └── DuplicateUserException.php
└── UseCases/
    ├── RegisterUserUseCase.php
    ├── UpdateUserUseCase.php
    └── AssignRoleUseCase.php
```

### Cas d'usage

- Enregistrer un nouvel utilisateur
- Mettre à jour les infos utilisateur
- Changer le mot de passe
- Assigner un rôle à un utilisateur

---

## 3️⃣ AccessControl Domain

### Objectif

Gérer les rôles et permissions **dynamiquement** - aucune permission codée en dur.

### Concepts clés

- **Permission** : Droit d'action granulaire (ex: "sale.create")
- **Role** : Ensemble de permissions
- **Permission Assignment** : Liaison rôle ↔ permission
- **YAML Source** : Fichier YAML est la source de vérité

### Structure

```
src/Domains/AccessControl/
├── Entities/
│   ├── Permission.php               # Entity permission
│   ├── Role.php                     # Entity rôle
│   └── RolePermission.php           # Association
├── ValueObjects/
│   ├── PermissionCode.php
│   ├── RoleName.php
│   └── PermissionGroup.php          # Catégorie permission (ex: "sales")
├── Repositories/
│   ├── PermissionRepository.php
│   └── RoleRepository.php
├── Services/
│   ├── AccessControlService.php     # Vérification accès
│   ├── PermissionParser.php         # Parser YAML → Permissions
│   └── RoleAssignmentService.php
├── Exceptions/
│   ├── PermissionDeniedException.php
│   ├── RoleNotFoundException.php
│   └── InvalidPermissionFileException.php
└── UseCases/
    ├── GeneratePermissionsFromYamlUseCase.php
    ├── CreateRoleUseCase.php
    ├── AssignPermissionToRoleUseCase.php
    └── CheckAccessUseCase.php
```

### Format YAML des permissions

```yaml
# storage/app/permissions.yaml
sales:
    - sale.create
    - sale.view
    - sale.edit
    - sale.refund

products:
    - product.create
    - product.update
    - product.delete
    - product.view

inventory:
    - stock.in
    - stock.out
    - stock.view

payments:
    - payment.process
    - payment.view
```

### Cas d'usage

- Générer permissions depuis fichier YAML
- Créer un rôle
- Assigner permissions à un rôle
- Vérifier si un utilisateur a une permission
- Lister permissions d'un utilisateur
- Modifier permissions d'un rôle (ajout/suppression)

### Règles métier

1. ❌ Les permissions ne sont **jamais supprimées automatiquement**
2. ✅ Nouvelles permissions du YAML → insétion en DB
3. ✅ Permissions existantes → marquées "old" mais conservées
4. ✅ Bouton "Générer permissions" est idempotent

---

## 4️⃣ Shop Domain

### Objectif

Configurer les points de vente avec leurs paramètres.

### Concepts clés

- **Shop** : Point de vente (physique ou online)
- **ShopSettings** : Paramètres de configuration
- **Currency**, **TaxRate** : Valeurs métier

### Structure

```
src/Domains/Shop/
├── Entities/Shop.php
├── ValueObjects/
│   ├── Currency.php
│   ├── TaxRate.php
│   └── ShopAddress.php
├── Repositories/ShopRepository.php
├── Services/ShopService.php
└── UseCases/
    ├── CreateShopUseCase.php
    ├── UpdateShopSettingsUseCase.php
```

### Cas d'usage

- Créer une boutique
- Configurer devise et taxes
- Mettre à jour infos magasin

---

## 5️⃣ Product Domain

### Objectif

Gérer le catalogue de produits.

### Concepts clés

- **Product** : Produit du catalogue
- **SKU** : Code unique du produit
- **ProductVariant** : Variantes (taille, couleur, etc.)
- **Category** : Catégorie de produits

### Structure

```
src/Domains/Product/
├── Entities/
│   ├── Product.php
│   └── ProductVariant.php
├── ValueObjects/
│   ├── SKU.php
│   ├── Price.php
│   └── ProductDescription.php
├── Repositories/ProductRepository.php
├── Services/ProductService.php
└── UseCases/
    ├── CreateProductUseCase.php
    ├── UpdateProductUseCase.php
    ├── DeleteProductUseCase.php
```

### Cas d'usage

- Créer un produit
- Ajouter des variantes
- Mettre à jour prix/stock
- Archiver un produit

---

## 6️⃣ Inventory Domain

### Objectif

Gérer le stock avec traçabilité des mouvements.

### Concepts clés

- **StockLevel** : Niveau de stock actuel
- **StockMovement** : Historique des mouvements
- **MovementType** : Entrée / Sortie

### Structure

```
src/Domains/Inventory/
├── Entities/
│   ├── StockLevel.php
│   └── StockMovement.php
├── ValueObjects/
│   ├── Quantity.php
│   ├── MovementType.php
│   └── StockReference.php
├── Repositories/
│   ├── StockLevelRepository.php
│   └── StockMovementRepository.php
├── Services/InventoryService.php
└── UseCases/
    ├── AdjustStockUseCase.php
    ├── GetStockLevelUseCase.php
    └── GetStockHistoryUseCase.php
```

### Cas d'usage

- Enregistrer entrée de stock
- Enregistrer sortie de stock
- Consulter niveau de stock
- Générer alertes stock faible

---

## 7️⃣ Sale Domain

### Objectif

Gérer les transactions de vente.

### Concepts clés

- **SalesOrder** : Facture / Commande de vente
- **SalesLineItem** : Ligne dans la facture
- **SalesStatus** : État de la vente (draft, finalized, returned)

### Structure

```
src/Domains/Sale/
├── Entities/
│   ├── SalesOrder.php
│   └── SalesLineItem.php
├── ValueObjects/
│   ├── OrderNumber.php
│   ├── TotalAmount.php
│   └── SalesStatus.php
├── Repositories/SalesOrderRepository.php
├── Services/
│   ├── SalesService.php
│   └── SalesCalculationService.php
├── Events/
│   ├── SaleCreatedEvent.php
│   └── SaleRefundedEvent.php
└── UseCases/
    ├── CreateSalesOrderUseCase.php
    ├── RefundSaleUseCase.php
    └── GetSalesReportUseCase.php
```

### Cas d'usage

- Créer une vente
- Ajouter produits à la vente
- Calculer total avec taxes
- Valider/Finaliser vente
- Rembourser une vente

---

## 8️⃣ Payment Domain

### Objectif

Traiter les paiements.

### Concepts clés

- **Payment** : Transaction de paiement
- **PaymentMethod** : Méthode (cash, card, check)
- **PaymentProvider** : Intégration externe

### Structure

```
src/Domains/Payment/
├── Entities/Payment.php
├── ValueObjects/
│   ├── PaymentMethod.php
│   ├── PaymentAmount.php
│   └── PaymentReference.php
├── Repositories/PaymentRepository.php
├── Services/
│   ├── PaymentService.php
│   └── PaymentProviderAdapter.php  # Abstraction externe
└── UseCases/
    ├── ProcessPaymentUseCase.php
    └── RefundPaymentUseCase.php
```

### Cas d'usage

- Enregistrer paiement
- Traiter remboursement
- Valider paiement

---

## 9️⃣ Subscription Domain

### Objectif

Gérer plans SaaS et facturation récurrente.

### Concepts clés

- **SubscriptionPlan** : Plan d'abonnement
- **Subscription** : Abonnement actif
- **Invoice** : Facture de facturation

### Structure

```
src/Domains/Subscription/
├── Entities/
│   ├── SubscriptionPlan.php
│   ├── Subscription.php
│   └── Invoice.php
├── ValueObjects/
│   ├── BillingPeriod.php
│   ├── PlanPrice.php
│   └── SubscriptionStatus.php
├── Repositories/
│   ├── SubscriptionPlanRepository.php
│   └── SubscriptionRepository.php
├── Services/
│   ├── SubscriptionService.php
│   └── BillingService.php
└── UseCases/
    ├── CreateSubscriptionUseCase.php
    ├── GenerateMonthlyInvoiceUseCase.php
    └── CancelSubscriptionUseCase.php
```

### Cas d'usage

- Créer plan d'abonnement
- S'abonner à un plan
- Générer factures mensuelles
- Annuler abonnement

---

## 🔟 Reporting Domain

### Objectif

Générer rapports et analytics.

### Concepts clés

- **Report** : Rapport agrégé
- **ReportFilter** : Critères de filtrage
- **Metric** : Métrique calculée

### Structure

```
src/Domains/Reporting/
├── Entities/Report.php
├── ValueObjects/
│   ├── ReportType.php
│   ├── DateRange.php
│   └── ReportMetric.php
├── Services/
│   ├── SalesReportService.php
│   ├── InventoryReportService.php
│   └── RevenueReportService.php
└── UseCases/
    ├── GenerateSalesReportUseCase.php
    ├── GenerateInventoryReportUseCase.php
    └── GetMetricsUseCase.php
```

### Cas d'usage

- Générer rapport de ventes
- Générer rapport d'inventaire
- Générer rapport de revenus
- Exporter en PDF/Excel

---

## Dépendances entre domaines

```
Tenant (base)
├── User (dépend de Tenant)
├── Shop (dépend de Tenant)
│   ├── Product (dépend de Shop)
│   │   └── Inventory (dépend de Product)
│   │       └── Sale (dépend de Inventory + Product)
│   │           ├── Payment (dépend de Sale)
│   │           └── Reporting (dépend de Sale)
│   └── Subscription (dépend de Tenant + Shop)
├── AccessControl (dépend de User + Tenant)
```

**Règle importante :** Un domaine ne dépend d'un autre que par ses interfaces publiques, jamais par ses détails d'implémentation.

---

## Prochaines étapes

- [ ] Implémenter complètement Tenant domain
- [ ] Implémenter User + AccessControl domains
- [ ] Créer migrations pour chaque domain
- [ ] Implémenter Use Cases
- [ ] Créer Controllers et Routes
- [ ] Créer composants React
