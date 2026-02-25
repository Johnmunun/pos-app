<?php

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO utilisé par les cas d'utilisation d'inventaire (ajout / ajustement de stock).
 * On transporte uniquement des types primitifs ; les ValueObjects (Quantity, ExpiryDate, etc.)
 * sont créés dans l'Application Layer / Domain.
 */
class UpdateStockDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $productId,
        public readonly int|float $quantity,
        public readonly ?string $batchNumber = null,
        public readonly ?string $expiryDate = null,
        public readonly ?string $supplierId = null,
        public readonly ?string $purchaseOrderId = null,
        public readonly ?int $createdBy = null
    ) {
    }
}
