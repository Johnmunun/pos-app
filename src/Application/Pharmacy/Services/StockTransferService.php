<?php

namespace Src\Application\Pharmacy\Services;

use Illuminate\Support\Facades\DB;
use Src\Domain\Pharmacy\Entities\StockTransfer;
use Src\Domain\Pharmacy\Entities\StockTransferItem;
use Src\Domain\Pharmacy\Entities\StockMovement;
use Src\Domain\Pharmacy\Repositories\StockTransferRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockTransferItemRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Shared\ValueObjects\Quantity;
use App\Models\Shop;

/**
 * Service métier pour la gestion des transferts de stock inter-magasins.
 *
 * Gère le cycle de vie complet des transferts :
 * - Création
 * - Ajout/modification d'items
 * - Validation (avec mise à jour des stocks)
 * - Annulation
 */
class StockTransferService
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository,
        private readonly StockTransferItemRepositoryInterface $itemRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository
    ) {}

    /**
     * Crée un nouveau transfert de stock
     *
     * @param string $pharmacyId
     * @param int $fromShopId
     * @param int $toShopId
     * @param int $createdBy
     * @param string|null $notes
     * @return StockTransfer
     * @throws \InvalidArgumentException
     */
    public function createTransfer(
        string $pharmacyId,
        int $fromShopId,
        int $toShopId,
        int $createdBy,
        ?string $notes = null
    ): StockTransfer {
        // Valider que les magasins sont différents
        if ($fromShopId === $toShopId) {
            throw new \InvalidArgumentException('Le magasin source et destination doivent être différents');
        }

        // Vérifier que les magasins appartiennent à la même pharmacie (tenant)
        $this->validateShopsBelongToSameTenant($fromShopId, $toShopId);

        $transfer = StockTransfer::create(
            $pharmacyId,
            (string) $fromShopId,
            (string) $toShopId,
            $createdBy,
            $notes
        );

        $this->transferRepository->save($transfer);

        return $transfer;
    }

    /**
     * Ajoute un item au transfert
     *
     * @param string $transferId
     * @param string $productId
     * @param int $quantity
     * @param string $pharmacyId
     * @return StockTransferItem
     * @throws \InvalidArgumentException
     */
    public function addItem(
        string $transferId,
        string $productId,
        int $quantity,
        string $pharmacyId
    ): StockTransferItem {
        $transfer = $this->transferRepository->findByIdAndPharmacy($transferId, $pharmacyId);

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé');
        }

        if (!$transfer->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        // Vérifier que le produit existe
        $product = $this->productRepository->findById($productId);
        if ($product === null) {
            throw new \InvalidArgumentException('Produit non trouvé');
        }

        // Vérifier si le produit est déjà dans le transfert
        $existingItem = $this->itemRepository->findByTransferAndProduct($transferId, $productId);
        if ($existingItem !== null) {
            // Mettre à jour la quantité existante
            $existingItem->updateQuantity($existingItem->getQuantity() + $quantity);
            $this->itemRepository->update($existingItem);
            return $existingItem;
        }

        // Créer un nouvel item
        $item = StockTransferItem::create($transferId, $productId, $quantity);
        $this->itemRepository->save($item);

        return $item;
    }

    /**
     * Met à jour la quantité d'un item
     *
     * @param string $itemId
     * @param int $quantity
     * @param string $pharmacyId
     * @return StockTransferItem
     */
    public function updateItemQuantity(
        string $itemId,
        int $quantity,
        string $pharmacyId
    ): StockTransferItem {
        $item = $this->itemRepository->findById($itemId);

        if ($item === null) {
            throw new \InvalidArgumentException('Item non trouvé');
        }

        $transfer = $this->transferRepository->findByIdAndPharmacy(
            $item->getStockTransferId(),
            $pharmacyId
        );

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé ou accès non autorisé');
        }

        if (!$transfer->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        $item->updateQuantity($quantity);
        $this->itemRepository->update($item);

        return $item;
    }

    /**
     * Supprime un item du transfert
     *
     * @param string $itemId
     * @param string $pharmacyId
     */
    public function removeItem(string $itemId, string $pharmacyId): void
    {
        $item = $this->itemRepository->findById($itemId);

        if ($item === null) {
            throw new \InvalidArgumentException('Item non trouvé');
        }

        $transfer = $this->transferRepository->findByIdAndPharmacy(
            $item->getStockTransferId(),
            $pharmacyId
        );

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé ou accès non autorisé');
        }

        if (!$transfer->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        $this->itemRepository->delete($itemId);
    }

    /**
     * Valide le transfert et effectue les mouvements de stock
     *
     * @param string $transferId
     * @param int $validatedBy
     * @param string $pharmacyId
     * @return StockTransfer
     * @throws \InvalidArgumentException|\Exception
     */
    public function validateTransfer(
        string $transferId,
        int $validatedBy,
        string $pharmacyId
    ): StockTransfer {
        return DB::transaction(function () use ($transferId, $validatedBy, $pharmacyId): StockTransfer {
            $transfer = $this->transferRepository->findByIdAndPharmacy($transferId, $pharmacyId);

            if ($transfer === null) {
                throw new \InvalidArgumentException('Transfert non trouvé');
            }

            $items = $this->itemRepository->findByTransfer($transferId);

            if (empty($items)) {
                throw new \InvalidArgumentException('Le transfert doit contenir au moins un produit');
            }

            // Vérifier le stock disponible pour chaque item
            foreach ($items as $item) {
                $product = $this->productRepository->findById($item->getProductId());
                if ($product === null) {
                    throw new \InvalidArgumentException('Produit non trouvé: ' . $item->getProductId());
                }

                $currentStock = $product->getStock()->getValue();
                if ($currentStock < $item->getQuantity()) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Stock insuffisant pour le produit "%s". Disponible: %d, Demandé: %d',
                            $product->getName(),
                            $currentStock,
                            $item->getQuantity()
                        )
                    );
                }
            }

            // Effectuer les mouvements de stock
            foreach ($items as $item) {
                $product = $this->productRepository->findById($item->getProductId());
                if ($product === null) {
                    continue;
                }

                $quantity = new Quantity($item->getQuantity());
                $reference = 'TRANSFER-' . $transfer->getReference();

                // Retirer du magasin source (règles Domain : entier si non divisible)
                $product->decreaseStock($quantity);
                $this->productRepository->update($product);

                // Mouvement OUT pour le magasin source
                $movementOut = StockMovement::out(
                    $transfer->getFromShopId(),
                    $item->getProductId(),
                    $quantity,
                    $reference,
                    $validatedBy
                );
                $this->stockMovementRepository->save($movementOut);

                // Mouvement IN pour le magasin destination
                $movementIn = StockMovement::in(
                    $transfer->getToShopId(),
                    $item->getProductId(),
                    $quantity,
                    $reference,
                    $validatedBy
                );
                $this->stockMovementRepository->save($movementIn);

                // Note: Dans un système multi-magasin complet, il faudrait aussi
                // ajouter le stock au produit du magasin destination
                // Ici on trace uniquement les mouvements car le stock est global par produit
            }

            // Valider le transfert
            $transfer->validate($validatedBy);
            $this->transferRepository->update($transfer);

            return $transfer;
        });
    }

    /**
     * Annule un transfert
     *
     * @param string $transferId
     * @param string $pharmacyId
     * @return StockTransfer
     */
    public function cancelTransfer(string $transferId, string $pharmacyId): StockTransfer
    {
        $transfer = $this->transferRepository->findByIdAndPharmacy($transferId, $pharmacyId);

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé');
        }

        $transfer->cancel();
        $this->transferRepository->update($transfer);

        return $transfer;
    }

    /**
     * Récupère un transfert avec ses items
     *
     * @param string $transferId
     * @param string $pharmacyId
     * @return StockTransfer|null
     */
    public function getTransfer(string $transferId, string $pharmacyId): ?StockTransfer
    {
        return $this->transferRepository->findByIdAndPharmacy($transferId, $pharmacyId);
    }

    /**
     * Liste les transferts d'une pharmacie
     *
     * @param string $pharmacyId
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function getTransfers(string $pharmacyId, array $filters = []): array
    {
        return $this->transferRepository->findByPharmacy($pharmacyId, $filters);
    }

    /**
     * Liste tous les transferts (pour ROOT)
     *
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function getAllTransfers(array $filters = []): array
    {
        return $this->transferRepository->findAll($filters);
    }

    /**
     * Vérifie que les deux magasins appartiennent au même tenant
     *
     * @param int $fromShopId
     * @param int $toShopId
     * @throws \InvalidArgumentException
     */
    private function validateShopsBelongToSameTenant(int $fromShopId, int $toShopId): void
    {
        /** @var Shop|null $fromShop */
        $fromShop = Shop::query()->find($fromShopId);
        /** @var Shop|null $toShop */
        $toShop = Shop::query()->find($toShopId);

        if ($fromShop === null) {
            throw new \InvalidArgumentException('Magasin source non trouvé');
        }

        if ($toShop === null) {
            throw new \InvalidArgumentException('Magasin destination non trouvé');
        }

        if ($fromShop->tenant_id !== $toShop->tenant_id) {
            throw new \InvalidArgumentException('Les deux magasins doivent appartenir à la même pharmacie');
        }
    }
}
