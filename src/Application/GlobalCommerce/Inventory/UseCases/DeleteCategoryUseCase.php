<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;

final class DeleteCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductRepositoryInterface $products
    ) {
    }

    public function execute(string $shopId, string $categoryId): void
    {
        $category = $this->categories->findById($categoryId);
        if (!$category || $category->getShopId() !== $shopId) {
            throw new \InvalidArgumentException('Catégorie introuvable.');
        }

        $productsInCategory = $this->products->search($shopId, '', ['category_id' => $categoryId]);
        if (count($productsInCategory) > 0) {
            throw new \InvalidArgumentException('Impossible de supprimer une catégorie qui contient des produits.');
        }

        $this->categories->delete($categoryId);
    }
}
