<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Application\GlobalCommerce\Inventory\DTO\UpdateProductDTO;
use Src\Domain\GlobalCommerce\Inventory\Entities\Product;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

final class UpdateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CategoryRepositoryInterface $categories
    ) {
    }

    public function execute(UpdateProductDTO $dto): Product
    {
        $product = $this->products->findById($dto->productId);
        if (!$product || $product->getShopId() !== $dto->shopId) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        $category = $this->categories->findById($dto->categoryId);
        if (!$category || $category->getShopId() !== $dto->shopId) {
            throw new \InvalidArgumentException('Catégorie invalide.');
        }

        if ($this->products->existsBySku($dto->shopId, $dto->sku, $dto->productId)) {
            throw new \InvalidArgumentException("SKU déjà utilisé: {$dto->sku}");
        }

        if ($dto->salePrice < 0 || $dto->purchasePrice < 0 || $dto->minimumStock < 0) {
            throw new \InvalidArgumentException('Prix et stock minimum doivent être >= 0.');
        }

        $stock = $dto->stock !== null ? new Quantity($dto->stock) : $product->getStock();
        if ($stock->getValue() < 0) {
            throw new \InvalidArgumentException('Le stock ne peut pas être négatif.');
        }

        $updated = new Product(
            $product->getId(),
            $dto->shopId,
            $dto->sku,
            $dto->barcode,
            $dto->name,
            $dto->description ?? '',
            $dto->categoryId,
            new Money($dto->purchasePrice, $dto->currency),
            new Money($dto->salePrice, $dto->currency),
            $stock,
            new Quantity($dto->minimumStock),
            $dto->isWeighted,
            $dto->hasExpiration,
            $dto->isActive
        );

        $this->products->update($updated);
        return $updated;
    }
}
