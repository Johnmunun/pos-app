<?php

namespace Src\Application\Quincaillerie\DTO;

/**
 * DTO création produit - Module Quincaillerie.
 * Aucune dépendance Pharmacy.
 */
class CreateProductDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $productCode,
        public readonly string $name,
        public readonly string $categoryId,
        public readonly float $price,
        public readonly string $currency,
        public readonly int $minimumStock,
        public readonly string $unit,
        public readonly ?string $description = null,
        public readonly ?float $cost = null,
        public readonly ?string $manufacturer = null,
        public readonly string $typeUnite = 'UNITE',
        public readonly int $quantiteParUnite = 1,
        public readonly bool $estDivisible = true,
        // Nouveaux champs image et prix
        public readonly ?string $imagePath = null,
        public readonly ?string $imageType = null,
        public readonly ?float $priceNormal = null,
        public readonly ?float $priceReduced = null,
        public readonly ?float $priceReductionPercent = null,
        public readonly ?float $priceNonNegotiable = null,
        public readonly ?float $priceWholesaleNormal = null,
        public readonly ?float $priceWholesaleReduced = null,
        public readonly ?float $priceNonNegotiableWholesale = null
    ) {}
}
