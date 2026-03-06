<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Application\GlobalCommerce\Inventory\DTO\UpdateCategoryDTO;
use Src\Domain\GlobalCommerce\Inventory\Entities\Category;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;

final class UpdateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories
    ) {
    }

    public function execute(UpdateCategoryDTO $dto): Category
    {
        $category = $this->categories->findById($dto->categoryId);
        if (!$category || $category->getShopId() !== $dto->shopId) {
            throw new \InvalidArgumentException('Catégorie introuvable.');
        }

        if ($this->categories->existsByName($dto->shopId, $dto->name, $dto->categoryId)) {
            throw new \InvalidArgumentException("Une catégorie avec le nom '{$dto->name}' existe déjà.");
        }

        if ($dto->parentId) {
            $parent = $this->categories->findById($dto->parentId);
            if (!$parent || $parent->getShopId() !== $dto->shopId) {
                throw new \InvalidArgumentException('Catégorie parente invalide.');
            }
            if ($dto->parentId === $dto->categoryId) {
                throw new \InvalidArgumentException('Une catégorie ne peut pas être sa propre parente.');
            }
        }

        $updated = new Category(
            $category->getId(),
            $dto->shopId,
            $dto->name,
            $dto->description ?? '',
            $dto->parentId,
            $dto->sortOrder,
            $dto->isActive
        );

        $this->categories->update($updated);
        return $updated;
    }
}
