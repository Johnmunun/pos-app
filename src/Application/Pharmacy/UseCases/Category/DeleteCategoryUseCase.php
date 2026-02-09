<?php

namespace Src\Application\Pharmacy\UseCases\Category;

use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;

/**
 * Use Case: DeleteCategory
 * 
 * Supprime une catégorie avec validation métier
 * Vérifie les permissions dans le Controller/Middleware
 */
class DeleteCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(string $categoryId): void
    {
        // Vérifier que la catégorie existe
        $category = $this->categoryRepository->findById($categoryId);
        if (!$category) {
            throw new \InvalidArgumentException("Catégorie non trouvée");
        }

        // Validation métier : ne pas supprimer si elle a des enfants
        $children = $this->categoryRepository->findByParent($categoryId);
        if (!empty($children)) {
            throw new \InvalidArgumentException("Impossible de supprimer une catégorie qui contient des sous-catégories");
        }

        // Validation métier : ne pas supprimer si elle a des produits
        // (Cette vérification sera faite via la relation dans le repository)

        // Supprimer
        $this->categoryRepository->delete($categoryId);
    }
}
