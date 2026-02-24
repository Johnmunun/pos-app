# Vérification depot_id – tables Pharmacy

## Résumé

Aujourd’hui, le **dépôt** est pris en compte **indirectement** : en changeant le dépôt dans la navbar, on charge le **shop** associé (`shops.depot_id`), et toutes les requêtes filtrent sur **shop_id**. Les données affichées (produits, ventes, stock, rapports, expirations) sont donc bien celles du dépôt sélectionné, mais les tables Pharmacy n’ont en général **pas** de colonne **depot_id**.

---

## 1. Tables qui ont déjà depot_id

| Table | depot_id | Remarque |
|-------|----------|----------|
| **pharmacy_inventories** | Oui | Modèle `InventoryModel` : `depot_id` dans fillable, scope `byDepot()` |
| **shops** | Oui | Migration `add_depot_id_to_tables` (database/migrations) |
| **stock_levels** (hors Pharmacy) | Oui | Idem |
| **stock_movements** (hors Pharmacy) | Oui | Idem |
| **sales** (hors Pharmacy) | Oui | Idem – table générique, pas `pharmacy_sales` |

---

## 2. Tables Pharmacy sans depot_id (uniquement shop_id)

| Table | Modèle | Colonne actuelle | Utilisation |
|-------|--------|------------------|-------------|
| **pharmacy_products** | ProductModel | shop_id | Produits, POS, rapports |
| **pharmacy_sales** | SaleModel | shop_id | Ventes |
| **pharmacy_sale_lines** | SaleLineModel | — (lié via sale_id) | Lignes de vente |
| **pharmacy_stock_movements** | StockMovementModel | shop_id | Mouvements de stock |
| **pharmacy_categories** | CategoryModel | shop_id | Catégories |
| **pharmacy_batches** | BatchModel | shop_id | Lots / expirations |
| **pharmacy_product_batches** | ProductBatchModel | shop_id | Lien produit–lot |
| **pharmacy_purchase_orders** | PurchaseOrderModel | shop_id | Achats |
| **pharmacy_purchase_order_lines** | — | — (lié via order) | Lignes d’achat |
| **pharmacy_customers** | CustomerModel | shop_id | Clients (module Pharmacy) |
| **pharmacy_suppliers** | SupplierModel | shop_id | Fournisseurs |
| **pharmacy_stock_transfers** | StockTransferModel | from_shop_id, to_shop_id | Transferts |
| **pharmacy_inventory_items** | — | — (lié via inventory_id) | Items d’inventaire (inventaire déjà avec depot_id) |

---

## 3. Comment le dépôt est utilisé aujourd’hui (sans casser)

Dans tous les contrôleurs Pharmacy, **getShopId()** fait :

1. Lire `session('current_depot_id')`
2. Si présent : `Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first()` → **shop_id**
3. Sinon : `user->shop_id ?? user->tenant_id`

Ensuite, toutes les requêtes filtrent sur **shop_id**. Donc :

- **Produits** : par shop_id (donc par dépôt via le shop).
- **Ventes** : par shop_id.
- **Stock / mouvements** : par shop_id.
- **Rapports** : par shop_id (donc périmètre = dépôt sélectionné).
- **Expirations / lots** : par shop_id.
- **Inventaires** : déjà filtrés par shop + depot (InventoryModel a depot_id et scope byDepot).

Aucun changement de logique n’est nécessaire pour que “tout soit par dépôt” : ça fonctionne déjà via **shop_id** et le lien **shops.depot_id**.

---

## 4. Intérêt d’ajouter depot_id dans les tables

- **Traçabilité** : voir directement “ce produit / cette vente / ce mouvement est du dépôt X” sans passer par la table `shops`.
- **Rapports / exports** : colonne “Dépôt” ou filtre par `depot_id` sans jointure.
- **Évolutions** : si un jour un shop a plusieurs dépôts ou un dépôt plusieurs shops, le champ depot_id reste pertinent.

---

## 5. Recommandation (sans rien casser)

1. **Court terme (déjà en place)**  
   - Ne rien casser : garder le filtrage actuel par **shop_id** dérivé du dépôt.  
   - Les écrans (produits, ventes, stock, rapports, expirations) restent cohérents avec le dépôt choisi.

2. **Ajout progressif de depot_id**  
   - Créer **une migration** qui ajoute `depot_id` (nullable, index, clé étrangère vers `depots`) aux tables listées en §2 qui n’ont que `shop_id`.  
   - Ne pas supprimer **shop_id** : garder les deux (comme pour `pharmacy_inventories`).  
   - Remplir `depot_id` à partir de `shops.depot_id` (UPDATE avec sous-requête ou script de backfill).  
   - Dans le code, continuer à filtrer par **shop_id** comme aujourd’hui ; on pourra ensuite, optionnellement, filtrer aussi (ou à la place) par **depot_id** une fois les données et les écrans adaptés.

3. **Tables à inclure en priorité dans la migration**  
   - pharmacy_products  
   - pharmacy_sales  
   - pharmacy_stock_movements  
   - pharmacy_categories  
   - pharmacy_batches  
   - pharmacy_product_batches (si la table a un shop_id)  
   - pharmacy_purchase_orders  
   - pharmacy_customers  
   - pharmacy_suppliers  

   Pour **pharmacy_stock_transfers**, on peut ajouter **from_depot_id** et **to_depot_id** (optionnel) pour cohérence.

---

## 6. Fichiers concernés (pour une future implémentation)

- **Migrations** : `src/Infrastructure/Pharmacy/Migrations/` ou `database/migrations/`
- **Modèles** : ajouter `depot_id` dans `$fillable` et `$casts` là où on l’ajoute en base
- **Contrôleurs** : aucun changement obligatoire tant qu’on garde le filtrage par **shop_id** ; plus tard, possibilité de filtrer par **depot_id** en plus ou à la place

---

*Document généré pour vérification “sans rien casser”.*
