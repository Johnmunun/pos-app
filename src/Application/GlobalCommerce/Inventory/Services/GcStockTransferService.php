<?php

namespace Src\Application\GlobalCommerce\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Src\Domain\GlobalCommerce\Inventory\Entities\Product;
use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransfer;
use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransferItem;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\StockTransferItemRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\StockTransferRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockMovementModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use App\Models\Shop;

/**
 * Service métier pour la gestion des transferts de stock inter-magasins (GlobalCommerce).
 */
class GcStockTransferService
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository,
        private readonly StockTransferItemRepositoryInterface $itemRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function createTransfer(
        string $tenantId,
        int $fromShopId,
        int $toShopId,
        int $createdBy,
        ?string $notes = null
    ): StockTransfer {
        if ($fromShopId === $toShopId) {
            throw new \InvalidArgumentException('Le magasin source et destination doivent être différents');
        }

        $this->validateShopsBelongToSameTenant($fromShopId, $toShopId);

        $transfer = StockTransfer::create(
            $tenantId,
            (string) $fromShopId,
            (string) $toShopId,
            $createdBy,
            $notes
        );

        $this->transferRepository->save($transfer);

        return $transfer;
    }

    public function addItem(
        string $transferId,
        string $productId,
        float $quantity,
        string $tenantId
    ): StockTransferItem {
        $transfer = $this->transferRepository->findByIdAndTenant($transferId, $tenantId);

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé');
        }

        if (!$transfer->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        $product = $this->productRepository->findById($productId);
        if ($product === null) {
            throw new \InvalidArgumentException('Produit non trouvé');
        }

        if ($product->getShopId() !== $transfer->getFromShopId()) {
            throw new \InvalidArgumentException('Le produit doit appartenir au magasin source');
        }

        $existingItem = $this->itemRepository->findByTransferAndProduct($transferId, $productId);
        if ($existingItem !== null) {
            $existingItem->updateQuantity($existingItem->getQuantity() + $quantity);
            $this->itemRepository->update($existingItem);
            return $existingItem;
        }

        $item = StockTransferItem::create($transferId, $productId, $quantity);
        $this->itemRepository->save($item);

        return $item;
    }

    public function updateItemQuantity(
        string $itemId,
        float $quantity,
        string $tenantId
    ): StockTransferItem {
        $item = $this->itemRepository->findById($itemId);

        if ($item === null) {
            throw new \InvalidArgumentException('Item non trouvé');
        }

        $transfer = $this->transferRepository->findByIdAndTenant(
            $item->getStockTransferId(),
            $tenantId
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

    public function removeItem(string $itemId, string $tenantId): void
    {
        $item = $this->itemRepository->findById($itemId);

        if ($item === null) {
            throw new \InvalidArgumentException('Item non trouvé');
        }

        $transfer = $this->transferRepository->findByIdAndTenant(
            $item->getStockTransferId(),
            $tenantId
        );

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé ou accès non autorisé');
        }

        if (!$transfer->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        $this->itemRepository->delete($itemId);
    }

    public function validateTransfer(
        string $transferId,
        int $validatedBy,
        string $tenantId
    ): StockTransfer {
        return DB::transaction(function () use ($transferId, $validatedBy, $tenantId): StockTransfer {
            $transfer = $this->transferRepository->findByIdAndTenant($transferId, $tenantId);

            if ($transfer === null) {
                throw new \InvalidArgumentException('Transfert non trouvé');
            }

            $items = $this->itemRepository->findByTransfer($transferId);

            if (empty($items)) {
                throw new \InvalidArgumentException('Le transfert doit contenir au moins un produit');
            }

            $fromShopId = $transfer->getFromShopId();
            $toShopId = $transfer->getToShopId();
            $reference = 'TRANSFER-' . $transfer->getReference();

            foreach ($items as $item) {
                $sourceProduct = $this->productRepository->findById($item->getProductId());
                if ($sourceProduct === null) {
                    throw new \InvalidArgumentException('Produit non trouvé: ' . $item->getProductId());
                }

                $qty = new Quantity((float) $item->getQuantity());
                $currentStock = $sourceProduct->getStock()->getValue();

                if ($currentStock < $qty->getValue()) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Stock insuffisant pour "%s". Disponible: %s, Demandé: %s',
                            $sourceProduct->getName(),
                            (string) $currentStock,
                            (string) $qty->getValue()
                        )
                    );
                }
            }

            foreach ($items as $item) {
                $sourceProduct = $this->productRepository->findById($item->getProductId());
                if ($sourceProduct === null) {
                    continue;
                }

                $qty = new Quantity((float) $item->getQuantity());

                $sourceProduct->removeStock($qty);
                $this->productRepository->update($sourceProduct);

                if (\Illuminate\Support\Facades\Schema::hasTable('gc_stock_movements')) {
                    GcStockMovementModel::create([
                        'id' => Uuid::uuid4()->toString(),
                        'shop_id' => (int) $fromShopId,
                        'product_id' => $sourceProduct->getId(),
                        'type' => 'OUT',
                        'quantity' => $qty->getValue(),
                        'reference' => $reference,
                        'reference_type' => 'transfer',
                        'reference_id' => $transferId,
                        'created_by' => $validatedBy,
                    ]);
                }

                $destProduct = $this->productRepository->findBySku($toShopId, $sourceProduct->getSku());

                if ($destProduct !== null) {
                    $destProduct->addStock($qty);
                    $this->productRepository->update($destProduct);
                } else {
                    $destProduct = $this->createProductInDestinationShop(
                        $sourceProduct,
                        $toShopId,
                        $qty->getValue()
                    );
                    $this->productRepository->save($destProduct);
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('gc_stock_movements')) {
                    GcStockMovementModel::create([
                        'id' => Uuid::uuid4()->toString(),
                        'shop_id' => (int) $toShopId,
                        'product_id' => $destProduct->getId(),
                        'type' => 'IN',
                        'quantity' => $qty->getValue(),
                        'reference' => $reference,
                        'reference_type' => 'transfer',
                        'reference_id' => $transferId,
                        'created_by' => $validatedBy,
                    ]);
                }
            }

            $transfer->validate($validatedBy);
            $this->transferRepository->update($transfer);

            return $transfer;
        });
    }

    public function cancelTransfer(string $transferId, string $tenantId): StockTransfer
    {
        $transfer = $this->transferRepository->findByIdAndTenant($transferId, $tenantId);

        if ($transfer === null) {
            throw new \InvalidArgumentException('Transfert non trouvé');
        }

        $transfer->cancel();
        $this->transferRepository->update($transfer);

        return $transfer;
    }

    public function getTransfer(string $transferId, string $tenantId): ?StockTransfer
    {
        return $this->transferRepository->findByIdAndTenant($transferId, $tenantId);
    }

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function getTransfers(string $tenantId, array $filters = []): array
    {
        return $this->transferRepository->findByTenant($tenantId, $filters);
    }

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function getAllTransfers(array $filters = []): array
    {
        return $this->transferRepository->findAll($filters);
    }

    private function createProductInDestinationShop(Product $source, string $toShopId, float $initialStock): Product
    {
        $categoryId = $this->resolveCategoryForDestinationShop(
            $source->getCategoryId(),
            $source->getShopId(),
            $toShopId
        );

        return new Product(
            Uuid::uuid4()->toString(),
            $toShopId,
            $source->getSku(),
            $source->getBarcode(),
            $source->getName(),
            $source->getDescription(),
            $categoryId,
            $source->getPurchasePrice(),
            $source->getSalePrice(),
            new Quantity($initialStock),
            $source->getMinimumStock(),
            $source->isWeighted(),
            $source->hasExpiration(),
            true
        );
    }

    private function resolveCategoryForDestinationShop(
        string $sourceCategoryId,
        string $sourceShopId,
        string $toShopId
    ): string {
        $sourceCategory = CategoryModel::find($sourceCategoryId);
        if ($sourceCategory === null) {
            return $this->getFirstCategoryId($toShopId);
        }

        $destCategory = CategoryModel::byShop($toShopId)
            ->where('name', $sourceCategory->name)
            ->where('is_active', true)
            ->first();

        if ($destCategory !== null) {
            return $destCategory->id;
        }

        return $this->getFirstCategoryId($toShopId);
    }

    private function getFirstCategoryId(string $shopId): string
    {
        $cat = CategoryModel::byShop($shopId)->where('is_active', true)->first();
        if ($cat === null) {
            throw new \InvalidArgumentException(
                'Aucune catégorie dans le magasin destination. Créez au moins une catégorie.'
            );
        }
        return $cat->id;
    }

    private function validateShopsBelongToSameTenant(int $fromShopId, int $toShopId): void
    {
        $fromShop = Shop::query()->find($fromShopId);
        $toShop = Shop::query()->find($toShopId);

        if ($fromShop === null) {
            throw new \InvalidArgumentException('Magasin source non trouvé');
        }

        if ($toShop === null) {
            throw new \InvalidArgumentException('Magasin destination non trouvé');
        }

        if ((string) $fromShop->tenant_id !== (string) $toShop->tenant_id) {
            throw new \InvalidArgumentException('Les deux magasins doivent appartenir au même tenant');
        }
    }
}
