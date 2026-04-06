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

    /**
     * @param list<string> $allowedShopIds
     */
    public function execute(array $allowedShopIds, string $categoryId): void
    {
        if ($allowedShopIds === []) {
            throw new \InvalidArgumentException('Catégorie introuvable.');
        }
        $category = $this->categories->findById($categoryId);
        if (!$category || !in_array($category->getShopId(), $allowedShopIds, true)) {
            throw new \InvalidArgumentException('Catégorie introuvable.');
        }

        $primary = $allowedShopIds[0];
        $productsInCategory = $this->products->search($primary, '', [
            'category_id' => $categoryId,
            'shop_ids' => $allowedShopIds,
        ]);
        if (count($productsInCategory) > 0) {
            throw new \InvalidArgumentException('Impossible de supprimer une catégorie qui contient des produits.');
        }

        $this->categories->delete($categoryId);
    }
}
