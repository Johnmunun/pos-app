<?php

namespace Src\Application\GlobalCommerce\Inventory\UseCases;

use Src\Application\GlobalCommerce\Inventory\DTO\CreateProductDTO;
use Src\Domain\GlobalCommerce\Inventory\Entities\Product;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

final class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly CategoryRepositoryInterface $categories
    ) {
    }

    public function execute(CreateProductDTO $dto): Product
    {
        $allowed = $dto->inventoryShopIds ?? [$dto->shopId];
        // Vérifier catégorie
        $category = $this->categories->findById($dto->categoryId);
        if (!$category || !in_array($category->getShopId(), $allowed, true)) {
            throw new \InvalidArgumentException('Catégorie invalide pour cette boutique.');
        }

        // Unicité SKU
        if ($this->products->existsBySku($dto->shopId, $dto->sku)) {
            throw new \InvalidArgumentException("SKU déjà utilisé: {$dto->sku}");
        }

        if ($dto->salePrice < 0 || $dto->purchasePrice < 0) {
            throw new \InvalidArgumentException('Les prix doivent être positifs.');
        }

        if ($dto->minimumStock < 0) {
            throw new \InvalidArgumentException('Le stock minimum doit être >= 0.');
        }

        $purchaseMoney = new Money($dto->purchasePrice, $dto->currency);
        $saleMoney = new Money($dto->salePrice, $dto->currency);
        $initialStock = new Quantity($dto->initialStock);
        $minimumStock = new Quantity($dto->minimumStock);

        $product = Product::create(
            $dto->shopId,
            $dto->sku,
            $dto->barcode,
            $dto->name,
            $dto->description ?? '',
            $dto->categoryId,
            $purchaseMoney,
            $saleMoney,
            $initialStock,
            $minimumStock,
            $dto->isWeighted,
            $dto->hasExpiration
        );

        $this->products->save($product);

        return $product;
    }
}

