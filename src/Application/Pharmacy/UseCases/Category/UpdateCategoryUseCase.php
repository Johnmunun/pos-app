<?php

namespace Src\Application\Pharmacy\UseCases\Category;

use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\UpdateCategoryDTO;
use Src\Domain\Pharmacy\Entities\Category;

/**
 * Use Case: UpdateCategory
 * 
 * Met à jour une catégorie existante avec validation métier
 * Vérifie les permissions dans le Controller/Middleware
 */
class UpdateCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(UpdateCategoryDTO $dto): Category
    {
        // Récupérer la catégorie existante
        $category = $this->categoryRepository->findById($dto->id);
        if (!$category) {
            throw new \InvalidArgumentException("Catégorie non trouvée");
        }

        // Validation : nom unique si modifié
        if ($dto->name !== null && $dto->name !== $category->getName()) {
            $this->validateUniqueName($dto->name, $category->getShopId(), $dto->id);
        }

        // Validation : parent existe et appartient au même shop
        if ($dto->parentId !== null) {
            if ($dto->parentId === $dto->id) {
                throw new \InvalidArgumentException("Une catégorie ne peut pas être son propre parent");
            }
            $this->validateParent($dto->parentId, $category->getShopId());
        }

        // Mettre à jour les propriétés
        if ($dto->name !== null) {
            $category->updateName($dto->name);
        }

        if ($dto->description !== null) {
            $category->updateDescription($dto->description);
        }

        if ($dto->parentId !== null) {
            $category->setParentId($dto->parentId);
        }

        if ($dto->sortOrder !== null) {
            $category->setSortOrder($dto->sortOrder);
        }

        if ($dto->isActive !== null) {
            if ($dto->isActive) {
                $category->activate();
            } else {
                $category->deactivate();
            }
        }

        // Sauvegarder
        $this->categoryRepository->update($category);

        return $category;
    }

    private function validateUniqueName(string $name, string $shopId, string $excludeId): void
    {
        if ($this->categoryRepository->existsByName($name, $shopId, $excludeId)) {
            throw new \InvalidArgumentException("Une catégorie avec le nom '{$name}' existe déjà pour cette boutique");
        }
    }

    private function validateParent(string $parentId, string $shopId): void
    {
        $parent = $this->categoryRepository->findById($parentId);
        if (!$parent) {
            throw new \InvalidArgumentException("La catégorie parente n'existe pas");
        }

        if ($parent->getShopId() !== $shopId) {
            throw new \InvalidArgumentException("La catégorie parente n'appartient pas à cette boutique");
        }
    }
}
