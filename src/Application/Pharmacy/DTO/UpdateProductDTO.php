<?php

namespace Src\Application\Pharmacy\DTO;

use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

class UpdateProductDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?Money $price = null,
        public readonly ?string $categoryId = null,
        public readonly ?bool $requiresPrescription = null,
        public readonly ?bool $isActive = null
    ) {}
}