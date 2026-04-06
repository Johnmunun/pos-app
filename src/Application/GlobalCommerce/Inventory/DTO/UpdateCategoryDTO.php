<?php

namespace Src\Application\GlobalCommerce\Inventory\DTO;

final class UpdateCategoryDTO
{
    public function __construct(
        public readonly string $categoryId,
        public readonly string $shopId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $parentId,
        public readonly int $sortOrder,
        public readonly bool $isActive,
        /** @var list<string>|null */
        public readonly ?array $inventoryShopIds = null
    ) {}
}
