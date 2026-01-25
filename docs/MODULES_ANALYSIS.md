# 📊 Analyse des Modules - Cohérence et Intégration

## 🔍 Problèmes Identifiés

### 1. **Incohérence Sidebar ↔ Modules**

**Problème** : La sidebar affiche des menus génériques qui ne reflètent pas les modules activés.

**Exemple** :
- Module "Pharmacie" définit : `medicines`, `batches`, `prescriptions`, `suppliers`
- Sidebar affiche : "Produits & Stock" (générique) au lieu de "Médicaments", "Ordonnances", etc.

### 2. **Manque de Menus Conditionnels**

**Problème** : La sidebar ne s'adapte pas selon le secteur du tenant.

**Secteurs disponibles** :
- `pharmacy` → Devrait afficher menus spécifiques pharmacie
- `butchery` → Devrait afficher menus spécifiques boucherie
- `kiosk` → Devrait afficher menus simplifiés
- `supermarket` → Devrait afficher menus supermarché

### 3. **Permissions Manquantes**

**Problème** : Les permissions définies dans `ModuleConfig.jsx` ne sont pas dans `permissions.yaml`.

**Exemples manquants** :
- `pharmacy.medicines.view`, `pharmacy.medicines.create`
- `butchery.meat_products.view`, `butchery.waste.manage`
- `kiosk.quick_sale.view`, `kiosk.stock.manage`
- `supermarket.aisles.view`, `supermarket.promotions.manage`

### 4. **Secteur Non Transmis au Frontend**

**Problème** : Le secteur du tenant n'est pas disponible dans les props Inertia.

**Impact** : Impossible de rendre la sidebar conditionnelle.

---

## ✅ Solutions Proposées

### 1. Ajouter le Secteur dans les Props Inertia

**Fichier** : `app/Http/Middleware/HandleInertiaRequests.php`

```php
public function share(Request $request): array
{
    $user = $request->user();
    $tenant = $user?->tenant;

    return [
        ...parent::share($request),
        'auth' => [
            'user' => $user,
            'permissions' => $user ? $user->permissionCodes() : [],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'code' => $tenant->code,
                'name' => $tenant->name,
                'sector' => $tenant->sector, // ← Ajouter le secteur
            ] : null,
        ],
        // ...
    ];
}
```

### 2. Rendre la Sidebar Conditionnelle

**Fichier** : `resources/js/Components/Layout/Sidebar.jsx`

Ajouter des groupes de navigation conditionnels selon le secteur :

```javascript
// Menus spécifiques par secteur
const getSectorMenus = (sector) => {
    const sectorMenus = {
        pharmacy: {
            key: 'pharmacy',
            label: 'Pharmacie',
            icon: Pill,
            permissions: ['module.pharmacy', 'pharmacy.medicines.view'],
            items: [
                { label: 'Médicaments', href: '#', permission: 'pharmacy.medicines.view', icon: Pill },
                { label: 'Lots & Expiration', href: '#', permission: 'pharmacy.batches.view', icon: Calendar },
                { label: 'Ordonnances', href: '#', permission: 'pharmacy.prescriptions.view', icon: FileText },
                { label: 'Fournisseurs', href: '#', permission: 'pharmacy.suppliers.view', icon: Truck },
            ],
        },
        butchery: {
            key: 'butchery',
            label: 'Boucherie',
            icon: UtensilsCrossed,
            permissions: ['module.butchery', 'butchery.meat_products.view'],
            items: [
                { label: 'Produits de viande', href: '#', permission: 'butchery.meat_products.view', icon: UtensilsCrossed },
                { label: 'Lots & Traçabilité', href: '#', permission: 'butchery.batches.view', icon: Package },
                { label: 'Gestion des déchets', href: '#', permission: 'butchery.waste.view', icon: Trash },
                { label: 'Découpe & Transformation', href: '#', permission: 'butchery.cutting.view', icon: Scissors },
            ],
        },
        kiosk: {
            key: 'kiosk',
            label: 'Kiosque',
            icon: Store,
            permissions: ['module.kiosk', 'kiosk.quick_sale.view'],
            items: [
                { label: 'Vente rapide', href: '#', permission: 'kiosk.quick_sale.view', icon: Zap },
                { label: 'Stock simplifié', href: '#', permission: 'kiosk.stock.view', icon: Package },
                { label: 'Produits unitaires', href: '#', permission: 'kiosk.products.view', icon: ShoppingBag },
            ],
        },
        supermarket: {
            key: 'supermarket',
            label: 'Supermarché',
            icon: ShoppingBag,
            permissions: ['module.supermarket', 'supermarket.aisles.view'],
            items: [
                { label: 'Rayons', href: '#', permission: 'supermarket.aisles.view', icon: LayoutGrid },
                { label: 'Variantes produits', href: '#', permission: 'supermarket.variants.view', icon: Layers },
                { label: 'Promotions', href: '#', permission: 'supermarket.promotions.view', icon: Tag },
                { label: 'Fidélité clients', href: '#', permission: 'supermarket.loyalty.view', icon: Award },
            ],
        },
    };
    
    return sectorMenus[sector] || null;
};
```

### 3. Ajouter les Permissions Manquantes

**Fichier** : `storage/app/permissions.yaml`

Ajouter toutes les permissions des modules :

```yaml
# ============================================
# PERMISSIONS MODULES SPÉCIFIQUES
# ============================================

modules:
  # Pharmacie
  - pharmacy.medicines.view
  - pharmacy.medicines.create
  - pharmacy.medicines.update
  - pharmacy.batches.view
  - pharmacy.batches.manage
  - pharmacy.prescriptions.view
  - pharmacy.prescriptions.create
  - pharmacy.suppliers.view
  - pharmacy.suppliers.manage

  # Boucherie
  - butchery.meat_products.view
  - butchery.meat_products.manage
  - butchery.batches.view
  - butchery.batches.manage
  - butchery.waste.view
  - butchery.waste.manage
  - butchery.cutting.view
  - butchery.cutting.manage

  # Kiosque
  - kiosk.quick_sale.view
  - kiosk.quick_sale.create
  - kiosk.stock.view
  - kiosk.stock.manage
  - kiosk.products.view
  - kiosk.products.manage

  # Supermarché
  - supermarket.aisles.view
  - supermarket.aisles.manage
  - supermarket.variants.view
  - supermarket.variants.manage
  - supermarket.promotions.view
  - supermarket.promotions.manage
  - supermarket.loyalty.view
  - supermarket.loyalty.manage
```

---

## 📋 Checklist de Cohérence

### Modules vs Fonctionnalités

- [x] **Pharmacie** : medicines, batches, prescriptions, suppliers → ✅ Cohérent
- [x] **Boucherie** : meat_products, batches, waste, cutting → ✅ Cohérent
- [x] **Kiosque** : quick_sale, simple_stock, unit_products → ✅ Cohérent
- [x] **Supermarché** : multi_aisles, variants, promotions, loyalty → ✅ Cohérent

### Modules vs Tables DB

- [x] **Pharmacie** : `medicines`, `medicine_batches`, `prescriptions` → ✅ Tables créées
- [x] **Boucherie** : `meat_products`, `meat_batches`, `waste_records` → ✅ Tables créées
- [x] **Kiosque** : Tables génériques (`products`, `sales`) → ✅ OK
- [x] **Supermarché** : Tables génériques + `promotions` → ✅ OK

### Modules vs Permissions

- [ ] **Pharmacie** : Permissions définies dans `ModuleConfig.jsx` → ❌ Manquantes dans `permissions.yaml`
- [ ] **Boucherie** : Permissions définies dans `ModuleConfig.jsx` → ❌ Manquantes dans `permissions.yaml`
- [ ] **Kiosque** : Permissions définies dans `ModuleConfig.jsx` → ❌ Manquantes dans `permissions.yaml`
- [ ] **Supermarché** : Permissions définies dans `ModuleConfig.jsx` → ❌ Manquantes dans `permissions.yaml`

### Sidebar vs Modules

- [ ] **Sidebar** : Menus génériques → ❌ Devrait être conditionnel selon secteur
- [ ] **Secteur** : Non transmis au frontend → ❌ À ajouter dans `HandleInertiaRequests`

---

## 🎯 Actions Prioritaires

1. ✅ **Ajouter le secteur dans les props Inertia**
2. ✅ **Ajouter toutes les permissions manquantes dans `permissions.yaml`**
3. ✅ **Rendre la sidebar conditionnelle selon le secteur**
4. ✅ **Tester la cohérence modules ↔ sidebar**

---

## 📝 Notes

- Les modules sont bien définis et cohérents avec les tables DB
- Le problème principal est l'intégration frontend (sidebar conditionnelle)
- Les permissions doivent être synchronisées après ajout dans `permissions.yaml`








