<?php

namespace Src\Application\Pharmacy\UseCases\Category;

use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\CreateCategoryDTO;
use Src\Domain\Pharmacy\Entities\Category;

/**
 * Use Case: CreateCategory
 * 
 * Crée une nouvelle catégorie avec validation métier
 * Vérifie les permissions dans le Controller/Middleware
 */
class CreateCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(CreateCategoryDTO $dto): Category
    {
        // Validation métier : nom unique par shop
        $this->validateUniqueName($dto->name, $dto->shopId);

        // Validation : parent existe et appartient au même shop
        if ($dto->parentId) {
            $this->validateParent($dto->parentId, $dto->shopId);
        }

        // Créer l'entité Category
        $category = Category::create(
            $dto->shopId,
            $dto->name,
            $dto->description,
            $dto->parentId,
            $dto->sortOrder
        );

        // Sauvegarder
        $this->categoryRepository->save($category);

        return $category;
    }

    private function validateUniqueName(string $name, string $shopId): void
    {
        if ($this->categoryRepository->existsByName($name, $shopId)) {
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
