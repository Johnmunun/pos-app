<?php

namespace Src\Application\Quincaillerie\DTO;

/**
 * DTO mise à jour produit - Module Quincaillerie.
 */
class UpdateProductDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly string $currency,
        public readonly string $categoryId,
        public readonly string $typeUnite,
        public readonly int $quantiteParUnite,
        public readonly bool $estDivisible,
        public readonly ?int $minimumStock = null,
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
