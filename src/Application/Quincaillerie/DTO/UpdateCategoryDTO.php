<?php

namespace Src\Application\Quincaillerie\DTO;

/**
 * DTO mise à jour catégorie - Module Quincaillerie.
 */
class UpdateCategoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $parentId = null,
        public readonly int $sortOrder = 0
    ) {}
}
