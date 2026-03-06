<?php

namespace Src\Application\GlobalCommerce\Procurement\UseCases;

use Src\Application\GlobalCommerce\Procurement\DTO\CreatePurchaseDTO;
use Src\Domain\GlobalCommerce\Procurement\Entities\Purchase;
use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel;

final class CreatePurchaseUseCase
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(CreatePurchaseDTO $dto): Purchase
    {
        if (empty($dto->lines)) {
            throw new \InvalidArgumentException('Le bon de commande doit contenir au moins une ligne.');
        }

        $supplier = SupplierModel::where('id', $dto->supplierId)->where('shop_id', $dto->shopId)->first();
        if (!$supplier) {
            throw new \InvalidArgumentException('Fournisseur invalide.');
        }

        $currency = $dto->currency;
        $purchaseLines = [];
        $totalAmount = 0.0;

        foreach ($dto->lines as $line) {
            $productId = $line['product_id'];
            $quantity = (float) $line['quantity'];
            $unitCost = (float) $line['unit_cost'];
            if ($quantity <= 0) {
                continue;
            }
            $product = $this->productRepository->findById($productId);
            if (!$product || $product->getShopId() !== $dto->shopId) {
                throw new \InvalidArgumentException("Produit invalide: {$productId}");
            }
            $lineTotal = round($quantity * $unitCost, 2);
            $totalAmount += $lineTotal;
            $purchaseLines[] = [
                'product_id' => $productId,
                'product_name' => $product->getName(),
                'ordered_quantity' => $quantity,
                'received_quantity' => 0.0,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        if (empty($purchaseLines)) {
            throw new \InvalidArgumentException('Au moins une ligne avec quantité > 0.');
        }

        $expectedAt = $dto->expectedAt ? new \DateTimeImmutable($dto->expectedAt) : null;
        $purchase = Purchase::create(
            $dto->shopId,
            $dto->supplierId,
            round($totalAmount, 2),
            $currency,
            $purchaseLines,
            $expectedAt,
            $dto->notes
        );

        $this->purchaseRepository->save($purchase);
        return $purchase;
    }
}
