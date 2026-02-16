<?php

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO pour une ligne de bon de commande (achat).
 */
class PurchaseOrderLineDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly int $orderedQuantity,
        public readonly float $unitCost
    ) {}
}

