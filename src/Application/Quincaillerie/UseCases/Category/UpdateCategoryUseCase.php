<?php

namespace Src\Application\Quincaillerie\UseCases\Category;

use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Application\Quincaillerie\DTO\UpdateCategoryDTO;

/**
 * Cas d'usage mise à jour catégorie - Module Quincaillerie.
 */
class UpdateCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(UpdateCategoryDTO $dto): void
    {
        $category = $this->categoryRepository->findById($dto->id);
        if (!$category) {
            throw new \InvalidArgumentException('Catégorie introuvable.');
        }

        if ($this->categoryRepository->existsByName($dto->name, $category->getShopId(), $dto->id)) {
            throw new \InvalidArgumentException('Une catégorie avec ce nom existe déjà.');
        }

        $category->updateName($dto->name);
        $category->updateDescription($dto->description ?? '');
        $category->setParentId($dto->parentId);
        $category->setSortOrder($dto->sortOrder);

        $this->categoryRepository->update($category);
    }
}
