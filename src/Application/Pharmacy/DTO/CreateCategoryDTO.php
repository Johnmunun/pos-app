<?php

namespace Src\Application\Pharmacy\DTO;

class CreateCategoryDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $parentId = null,
        public readonly int $sortOrder = 0
    ) {}
}
