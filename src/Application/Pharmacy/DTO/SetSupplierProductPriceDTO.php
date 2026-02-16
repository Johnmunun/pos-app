<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO: SetSupplierProductPriceDTO
 *
 * Données pour définir/mettre à jour un prix fournisseur-produit.
 */
final readonly class SetSupplierProductPriceDTO
{
    public function __construct(
        public string $supplierId,
        public string $productId,
        public float $normalPrice,
        public ?float $agreedPrice = null,
        public float $taxRate = 0,
        public ?string $effectiveFrom = null
    ) {
    }
}
