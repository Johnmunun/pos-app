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
        public readonly ?int $minimumStock = null
    ) {}
}
