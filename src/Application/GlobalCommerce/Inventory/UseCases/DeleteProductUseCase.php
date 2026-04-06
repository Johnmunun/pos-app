<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;

final class DeleteProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $products
    ) {
    }

    /**
     * @param list<string> $allowedShopIds Boutique canonique + éventuel shop_id legacy (ex. tenant_id).
     */
    public function execute(array $allowedShopIds, string $productId): void
    {
        $product = $this->products->findById($productId);
        if (!$product || !in_array($product->getShopId(), $allowedShopIds, true)) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }
        $this->products->delete($productId);
    }
}
