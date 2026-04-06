<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Application\GlobalCommerce\Inventory\DTO\CreateCategoryDTO;
use Src\Domain\GlobalCommerce\Inventory\Entities\Category;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;

final class CreateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories
    ) {
    }

    public function execute(CreateCategoryDTO $dto): Category
    {
        $allowed = $dto->inventoryShopIds ?? [$dto->shopId];
        if ($this->categories->existsByName($dto->shopId, $dto->name)) {
            throw new \InvalidArgumentException("Une catégorie avec le nom '{$dto->name}' existe déjà.");
        }

        if ($dto->parentId) {
            $parent = $this->categories->findById($dto->parentId);
            if (!$parent || !in_array($parent->getShopId(), $allowed, true)) {
                throw new \InvalidArgumentException('Catégorie parente invalide.');
            }
        }

        $category = Category::create(
            $dto->shopId,
            $dto->name,
            $dto->description,
            $dto->parentId,
            $dto->sortOrder
        );

        $this->categories->save($category);

        return $category;
    }
}

