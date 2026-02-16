<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\Services;

use Illuminate\Support\Facades\DB;
use Src\Domain\Pharmacy\Entities\Inventory;
use Src\Domain\Pharmacy\Entities\InventoryItem;
use Src\Domain\Pharmacy\Repositories\InventoryRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\InventoryItemRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Domain\Pharmacy\Entities\StockMovement;
use Src\Shared\ValueObjects\Quantity;
use Src\Infrastructure\Pharmacy\Models\ProductModel;

/**
 * Service Application : InventoryService
 *
 * Gère la logique métier des inventaires physiques.
 * - Création avec snapshot du stock système
 * - Saisie des quantités comptées
 * - Validation avec ajustement du stock et traçabilité
 */
class InventoryService
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository,
        private InventoryItemRepositoryInterface $inventoryItemRepository,
        private ProductRepositoryInterface $productRepository,
        private StockMovementRepositoryInterface $stockMovementRepository
    ) {}

    /**
     * Crée un nouvel inventaire en brouillon
     * 
     * @param string $shopId
     * @param int $createdBy
     * @return Inventory
     */
    public function createInventory(string $shopId, int $createdBy): Inventory
    {
        return DB::transaction(function () use ($shopId, $createdBy): Inventory {
            // Créer l'inventaire en brouillon
            $inventory = Inventory::create($shopId, $createdBy);
            $this->inventoryRepository->save($inventory);

            return $inventory;
        });
    }

    /**
     * Démarre un inventaire et crée le snapshot des produits
     * 
     * @param string $inventoryId
     * @param string $shopId
     * @param array<string>|null $productIds Optionnel: liste des produits à inventorier (null = tous)
     * @return Inventory
     */
    public function startInventory(string $inventoryId, string $shopId, ?array $productIds = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $shopId, $productIds): Inventory {
            $inventory = $this->inventoryRepository->findByIdAndShop($inventoryId, $shopId);
            
            if (!$inventory) {
                throw new \DomainException('Inventaire non trouvé.');
            }

            // Démarrer l'inventaire
            $inventory->start();
            $this->inventoryRepository->save($inventory);

            // Récupérer les produits à inventorier
            $query = ProductModel::where('shop_id', $shopId)->where('is_active', true);
            
            if ($productIds !== null && count($productIds) > 0) {
                $query->whereIn('id', $productIds);
            }

            $products = $query->get();

            // Créer les items avec snapshot du stock système
            $items = [];
            foreach ($products as $product) {
                $item = InventoryItem::create(
                    $inventory->getId(),
                    $product->id,
                    (int) ($product->stock ?? 0)
                );
                $items[] = $item;
            }

            $this->inventoryItemRepository->saveMany($items);

            return $inventory;
        });
    }

    /**
     * Met à jour la quantité comptée d'un item
     * 
     * @param string $inventoryId
     * @param string $shopId
     * @param string $productId
     * @param int $countedQuantity
     * @return InventoryItem
     */
    public function updateItemCount(
        string $inventoryId,
        string $shopId,
        string $productId,
        int $countedQuantity
    ): InventoryItem {
        return DB::transaction(function () use ($inventoryId, $shopId, $productId, $countedQuantity): InventoryItem {
            // Vérifier que l'inventaire existe et appartient à la boutique
            $inventory = $this->inventoryRepository->findByIdAndShop($inventoryId, $shopId);
            
            if (!$inventory) {
                throw new \DomainException('Inventaire non trouvé.');
            }

            if (!$inventory->canBeEdited()) {
                throw new \DomainException('Cet inventaire ne peut plus être modifié.');
            }

            // Trouver ou créer l'item
            $item = $this->inventoryItemRepository->findByInventoryAndProduct($inventoryId, $productId);
            
            if (!$item) {
                throw new \DomainException('Produit non trouvé dans cet inventaire.');
            }

            // Mettre à jour la quantité comptée
            $item->updateCountedQuantity($countedQuantity);
            $this->inventoryItemRepository->save($item);

            return $item;
        });
    }

    /**
     * Met à jour plusieurs items en une fois
     * 
     * @param string $inventoryId
     * @param string $shopId
     * @param array<string, int> $counts [productId => countedQuantity]
     */
    public function updateItemCounts(string $inventoryId, string $shopId, array $counts): void
    {
        DB::transaction(function () use ($inventoryId, $shopId, $counts): void {
            $inventory = $this->inventoryRepository->findByIdAndShop($inventoryId, $shopId);
            
            if (!$inventory) {
                throw new \DomainException('Inventaire non trouvé.');
            }

            if (!$inventory->canBeEdited()) {
                throw new \DomainException('Cet inventaire ne peut plus être modifié.');
            }

            foreach ($counts as $productId => $countedQuantity) {
                $item = $this->inventoryItemRepository->findByInventoryAndProduct($inventoryId, $productId);
                
                if ($item) {
                    $item->updateCountedQuantity((int) $countedQuantity);
                    $this->inventoryItemRepository->save($item);
                }
            }
        });
    }

    /**
     * Valide l'inventaire et applique les ajustements de stock
     * 
     * @param string $inventoryId
     * @param string $shopId
     * @param int $validatedBy
     * @return Inventory
     */
    public function validateInventory(string $inventoryId, string $shopId, int $validatedBy): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $shopId, $validatedBy): Inventory {
            $inventory = $this->inventoryRepository->findByIdAndShop($inventoryId, $shopId);
            
            if (!$inventory) {
                throw new \DomainException('Inventaire non trouvé.');
            }

            // Valider l'inventaire (changement de statut)
            $inventory->validate($validatedBy);

            // Récupérer tous les items
            $items = $this->inventoryItemRepository->findByInventory($inventoryId);

            // Appliquer les ajustements de stock pour chaque item avec écart
            foreach ($items as $item) {
                if (!$item->isCounted()) {
                    continue; // Ignorer les items non comptés
                }

                $difference = $item->getDifference();
                
                if ($difference === 0) {
                    continue; // Pas d'écart, rien à faire
                }

                // Récupérer le produit
                $product = $this->productRepository->findById($item->getProductId());
                
                if (!$product) {
                    continue;
                }

                // Appliquer l'ajustement de stock
                $reference = 'INV:' . $inventory->getReference();
                
                if ($difference > 0) {
                    // Écart positif: on a plus en réalité qu'en système → ajouter
                    $product->addStock(new Quantity($difference));
                    $this->productRepository->update($product);

                    // Enregistrer le mouvement
                    $movement = StockMovement::adjustment(
                        $shopId,
                        $item->getProductId(),
                        new Quantity($difference),
                        $reference . ':+' . $difference,
                        $validatedBy
                    );
                    $this->stockMovementRepository->save($movement);
                } else {
                    // Écart négatif: on a moins en réalité qu'en système → retirer
                    $absValue = abs($difference);
                    
                    // Vérifier que le stock est suffisant
                    if ($product->getStock()->getValue() >= $absValue) {
                        $product->removeStock(new Quantity($absValue));
                        $this->productRepository->update($product);

                        // Enregistrer le mouvement
                        $movement = StockMovement::adjustment(
                            $shopId,
                            $item->getProductId(),
                            new Quantity($absValue),
                            $reference . ':-' . $absValue,
                            $validatedBy
                        );
                        $this->stockMovementRepository->save($movement);
                    }
                }
            }

            // Sauvegarder l'inventaire validé
            $this->inventoryRepository->save($inventory);

            return $inventory;
        });
    }

    /**
     * Annule un inventaire
     * 
     * @param string $inventoryId
     * @param string $shopId
     * @return Inventory
     */
    public function cancelInventory(string $inventoryId, string $shopId): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $shopId): Inventory {
            $inventory = $this->inventoryRepository->findByIdAndShop($inventoryId, $shopId);
            
            if (!$inventory) {
                throw new \DomainException('Inventaire non trouvé.');
            }

            $inventory->cancel();
            $this->inventoryRepository->save($inventory);

            return $inventory;
        });
    }

    /**
     * Récupère un inventaire avec ses items
     * 
     * @param string $inventoryId
     * @param string $shopId
     * @return array{inventory: Inventory, items: InventoryItem[]}|null
     */
    public function getInventoryWithItems(string $inventoryId, string $shopId): ?array
    {
        $inventory = $this->inventoryRepository->findByIdAndShop($inventoryId, $shopId);
        
        if (!$inventory) {
            return null;
        }

        $items = $this->inventoryItemRepository->findByInventory($inventoryId);

        return [
            'inventory' => $inventory,
            'items' => $items,
        ];
    }

    /**
     * Récupère tous les inventaires d'une boutique
     * 
     * @param string $shopId
     * @param array<string, mixed> $filters
     * @return Inventory[]
     */
    public function getInventories(string $shopId, array $filters = []): array
    {
        return $this->inventoryRepository->findByShop($shopId, $filters);
    }

    /**
     * Récupère tous les inventaires (ROOT)
     * 
     * @param array<string, mixed> $filters
     * @return Inventory[]
     */
    public function getAllInventories(array $filters = []): array
    {
        return $this->inventoryRepository->findAll($filters);
    }
}
