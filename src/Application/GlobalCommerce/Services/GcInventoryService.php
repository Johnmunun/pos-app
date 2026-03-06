<?php

declare(strict_types=1);

namespace Src\Application\GlobalCommerce\Services;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcInventoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcInventoryItemModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Shared\ValueObjects\Quantity;

/**
 * Service d'inventaire physique - Module Global Commerce
 */
class GcInventoryService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function createInventory(string $shopId, int $createdBy): GcInventoryModel
    {
        return DB::transaction(function () use ($shopId, $createdBy): GcInventoryModel {
            $reference = 'INV-GC-' . strtoupper(substr(Uuid::uuid4()->toString(), 0, 8));
            return GcInventoryModel::create([
                'id' => Uuid::uuid4()->toString(),
                'shop_id' => (int) $shopId,
                'reference' => $reference,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);
        });
    }

    public function startInventory(string $inventoryId, string $shopId, ?array $productIds = null): GcInventoryModel
    {
        return DB::transaction(function () use ($inventoryId, $shopId, $productIds): GcInventoryModel {
            $inventory = GcInventoryModel::where('id', $inventoryId)->where('shop_id', (int) $shopId)->firstOrFail();

            if ($inventory->status !== 'draft') {
                throw new \DomainException('Cet inventaire ne peut plus être démarré.');
            }

            $inventory->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $query = ProductModel::where('shop_id', (int) $shopId)->where('is_active', true);
            if ($productIds !== null && count($productIds) > 0) {
                $query->whereIn('id', $productIds);
            }
            $products = $query->get();

            foreach ($products as $product) {
                GcInventoryItemModel::create([
                    'id' => Uuid::uuid4()->toString(),
                    'inventory_id' => $inventory->id,
                    'product_id' => $product->id,
                    'system_quantity' => (float) ($product->stock ?? 0),
                ]);
            }

            return $inventory->fresh();
        });
    }

    public function updateItemCount(string $inventoryId, string $shopId, string $productId, float $countedQuantity): GcInventoryItemModel
    {
        return DB::transaction(function () use ($inventoryId, $shopId, $productId, $countedQuantity): GcInventoryItemModel {
            $inventory = GcInventoryModel::where('id', $inventoryId)->where('shop_id', (int) $shopId)->firstOrFail();

            if (!in_array($inventory->status, ['draft', 'in_progress'])) {
                throw new \DomainException('Cet inventaire ne peut plus être modifié.');
            }

            $item = GcInventoryItemModel::where('inventory_id', $inventoryId)->where('product_id', $productId)->firstOrFail();
            $systemQty = (float) $item->system_quantity;
            $diff = $countedQuantity - $systemQty;

            $item->update([
                'counted_quantity' => $countedQuantity,
                'difference' => $diff,
            ]);

            return $item->fresh();
        });
    }

    public function updateItemCounts(string $inventoryId, string $shopId, array $counts): void
    {
        DB::transaction(function () use ($inventoryId, $shopId, $counts): void {
            $inventory = GcInventoryModel::where('id', $inventoryId)->where('shop_id', (int) $shopId)->firstOrFail();

            if (!in_array($inventory->status, ['draft', 'in_progress'])) {
                throw new \DomainException('Cet inventaire ne peut plus être modifié.');
            }

            foreach ($counts as $productId => $countedQuantity) {
                $item = GcInventoryItemModel::where('inventory_id', $inventoryId)->where('product_id', $productId)->first();
                if ($item) {
                    $systemQty = (float) $item->system_quantity;
                    $item->update([
                        'counted_quantity' => (float) $countedQuantity,
                        'difference' => (float) $countedQuantity - $systemQty,
                    ]);
                }
            }
        });
    }

    public function validateInventory(string $inventoryId, string $shopId, int $validatedBy): GcInventoryModel
    {
        return DB::transaction(function () use ($inventoryId, $shopId, $validatedBy): GcInventoryModel {
            $inventory = GcInventoryModel::where('id', $inventoryId)->where('shop_id', (int) $shopId)->firstOrFail();

            if ($inventory->status !== 'in_progress') {
                throw new \DomainException('Cet inventaire ne peut pas être validé.');
            }

            $items = GcInventoryItemModel::where('inventory_id', $inventoryId)->get();

            foreach ($items as $item) {
                if ($item->counted_quantity === null) {
                    continue;
                }
                $difference = (float) $item->difference;
                if (abs($difference) < 0.0001) {
                    continue;
                }

                $product = $this->productRepository->findById($item->product_id);
                if (!$product) {
                    continue;
                }

                if ($difference > 0) {
                    $product->addStock(new Quantity($difference));
                    $this->productRepository->update($product);
                } else {
                    $absValue = abs($difference);
                    if ($product->getStock()->getValue() >= $absValue) {
                        $product->removeStock(new Quantity($absValue));
                        $this->productRepository->update($product);
                    }
                }
            }

            $inventory->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => $validatedBy,
            ]);

            return $inventory->fresh();
        });
    }

    public function cancelInventory(string $inventoryId, string $shopId): GcInventoryModel
    {
        $inventory = GcInventoryModel::where('id', $inventoryId)->where('shop_id', (int) $shopId)->firstOrFail();

        if ($inventory->status === 'validated') {
            throw new \DomainException('Un inventaire validé ne peut pas être annulé.');
        }

        $inventory->update(['status' => 'cancelled']);
        return $inventory->fresh();
    }
}
