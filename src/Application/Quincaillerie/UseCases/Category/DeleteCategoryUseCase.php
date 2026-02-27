<?php

namespace Src\Application\Quincaillerie\UseCases\Category;

use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;

/**
 * Cas d'usage suppression catégorie - Module Quincaillerie.
 */
class DeleteCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(string $id): void
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            throw new \InvalidArgumentException('Catégorie introuvable.');
        }

        $this->categoryRepository->delete($id);
    }
}
