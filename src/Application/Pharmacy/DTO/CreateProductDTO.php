<?php

namespace Src\Application\Pharmacy\DTO;

class CreateProductDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $productCode,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $categoryId,
        public readonly float $price,
        public readonly string $currency,
        public readonly ?float $cost = null,
        public readonly int $minimumStock,
        public readonly string $unit,
        public readonly ?string $medicineType = null,
        public readonly ?string $dosage = null,
        public readonly bool $prescriptionRequired = false,
        public readonly ?string $manufacturer = null,
        public readonly ?string $supplierId = null
    ) {}
}
