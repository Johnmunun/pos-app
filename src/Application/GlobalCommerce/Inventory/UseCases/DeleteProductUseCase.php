<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;

final class DeleteProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $products
    ) {
    }

    public function execute(string $shopId, string $productId): void
    {
        $product = $this->products->findById($productId);
        if (!$product || $product->getShopId() !== $shopId) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }
        $this->products->delete($productId);
    }
}
