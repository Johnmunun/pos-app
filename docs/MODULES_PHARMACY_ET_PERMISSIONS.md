# Résumé : modules Pharmacy (Vente, Achat, Stock) et permissions

## Ce qui a été implémenté

### 1. Module **Vente (Sales)**
- **Domaine** : entités `Sale`, `SaleLine` ; interfaces `SaleRepositoryInterface`, `SaleLineRepositoryInterface`.
- **Use cases** : création de brouillon, mise à jour des lignes (panier), association client, finalisation (paiement + sortie de stock), annulation.
- **Back-office** : `SaleController` — liste des ventes (filtres date/statut), écran caisse (panier, client, montant payé/solde), détail d’une vente.
- **Front** : pages Inertia `Pharmacy/Sales/Index`, `Create` (POS), `Show`. Panier, recherche produit, choix client, validation avec décrément de stock et référence `SALE-{id}` dans les mouvements de stock.

### 2. Module **Achat (Purchases)**
- **Domaine** : entités `PurchaseOrder`, `PurchaseOrderLine` ; interfaces `PurchaseOrderRepositoryInterface`, `PurchaseOrderLineRepositoryInterface`.
- **Use cases** : création de bon de commande (DRAFT), confirmation, réception (entrée de stock par ligne, référence PO), annulation.
- **Back-office** : `PurchaseController` — liste des bons de commande, création (fournisseur + lignes), détail, actions Confirmer / Réception / Annuler. `SupplierController` : création de fournisseurs.
- **Front** : pages `Pharmacy/Purchases/Index`, `Create`, `Show`. Création PO avec fournisseur et lignes produit (qté, prix d’achat) ; réception enregistrée déclenche les entrées de stock avec référence au PO.

### 3. Module **Stock** (compléments)
- **Mouvements** : `created_by` (utilisateur) et référence (ex. `SALE-xxx`, `PO-xxx`) sur chaque mouvement. `UpdateStockUseCase` : `removeStock(..., $createdBy, $reference)` ; `addStock` via DTO avec `createdBy` ; `adjustStock` avec `$createdBy`.
- **Back-office** : `StockController::movementsIndex` — liste globale des mouvements avec filtres (type, dates, référence).
- **Front** : page `Pharmacy/Stock/Movements` + lien depuis la page Stock. Modal historique par produit inchangé.

### 4. **Infrastructure**
- **Tables** : `pharmacy_sales`, `pharmacy_sale_lines`, `pharmacy_purchase_orders`, `pharmacy_purchase_order_lines`, `pharmacy_suppliers`.
- **Repositories / modèles** Eloquent pour Sales, SaleLines, PurchaseOrders, PurchaseOrderLines, Suppliers. Enregistrement des use cases et repositories dans `PharmacyServiceProvider`.

---

## Permissions ajoutées (migrations)

| Code | Description |
|------|-------------|
| `pharmacy.sales.view` | Voir les ventes |
| `pharmacy.sales.manage` | Créer et gérer les ventes (caisse) |
| `pharmacy.sales.cancel` | Annuler une vente (brouillon) |
| `pharmacy.purchases.view` | Voir les bons de commande |
| `pharmacy.purchases.manage` | Créer et gérer les bons de commande |
| `pharmacy.purchases.receive` | Enregistrer la réception de marchandises |

**Stock** (déjà présentes ou existantes) : `stock.view`, `stock.adjust`, `stock.movement.view`.

Les permissions sont insérées par la migration `2026_02_11_190000_add_sales_purchases_permissions.php`. Il faut les attribuer aux rôles (ex. ROOT / Pharmacie) via l’interface Access Manager ou un seeder.

---

## Toast après synchronisation des permissions

- Le contrôleur envoie désormais un **message en session** avec `Redirect::back()->with('message', $message)` (au lieu de `with('flash', [...])`), compatible avec le middleware Inertia qui expose `flash.message`.
- La page **Permissions** affiche un **toast de succès** (ou d’erreur) au chargement lorsque `flash.message` ou `flash.error` est présent, et le callback `onSuccess` du sync ne déclenche plus de second toast.
- Après un clic sur « Générer depuis permissions.yaml », tu as toujours la redirection avec la liste à jour, et **un seul toast** avec le message détaillé (créées / mises à jour / marquées obsolètes).
