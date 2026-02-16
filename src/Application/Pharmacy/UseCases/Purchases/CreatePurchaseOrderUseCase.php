<?php

namespace Src\Application\Pharmacy\UseCases\Purchases;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Application\Pharmacy\DTO\PurchaseOrderLineDTO;
use Src\Domain\Pharmacy\Entities\PurchaseOrder;
use Src\Domain\Pharmacy\Entities\PurchaseOrderLine;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderRepositoryInterface;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Use case : créer un bon de commande (PO) en statut DRAFT.
 */
class CreatePurchaseOrderUseCase
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderLineRepositoryInterface $purchaseOrderLineRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * @param string $shopId
     * @param string $supplierId
     * @param string $currency
     * @param int $createdBy
     * @param DateTimeImmutable|null $expectedAt
     * @param PurchaseOrderLineDTO[] $lines
     */
    public function execute(
        string $shopId,
        string $supplierId,
        string $currency,
        int $createdBy,
        ?DateTimeImmutable $expectedAt,
        array $lines
    ): PurchaseOrder {
        return DB::transaction(function () use ($shopId, $supplierId, $currency, $createdBy, $expectedAt, $lines): PurchaseOrder {
            if (count($lines) === 0) {
                throw new \InvalidArgumentException('Purchase order must contain at least one line');
            }

            // 1. Créer et sauvegarder d'abord le bon de commande (pour la FK)
            $po = PurchaseOrder::createDraft(
                $shopId,
                $supplierId,
                $currency,
                $expectedAt,
                $createdBy
            );
            $this->purchaseOrderRepository->save($po);

            // 2. Ensuite créer les lignes
            $total = new Money(0, $currency);

            foreach ($lines as $lineDto) {
                $product = $this->productRepository->findById($lineDto->productId);
                if (!$product) {
                    throw new \InvalidArgumentException('Product not found for purchase order line');
                }

                $orderedQty = new Quantity($lineDto->orderedQuantity);
                $unitCost = new Money($lineDto->unitCost, $currency);

                $line = PurchaseOrderLine::create(
                    $po->getId(),
                    $lineDto->productId,
                    $orderedQty,
                    $unitCost
                );

                $this->purchaseOrderLineRepository->save($line);
                $total = $total->add($line->getLineTotal());
            }

            // 3. Mettre à jour le total et resauvegarder
            $po->updateTotal($total);
            $this->purchaseOrderRepository->save($po);

            return $po;
        });
    }
}

