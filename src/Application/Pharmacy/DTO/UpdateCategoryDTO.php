<?php

namespace Src\Application\Pharmacy\DTO;

class UpdateCategoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $parentId = null,
        public readonly ?int $sortOrder = null,
        public readonly ?bool $isActive = null
    ) {}
}
