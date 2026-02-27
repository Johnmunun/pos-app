<?php

namespace Src\Application\Quincaillerie\UseCases\Product;

use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Application\Quincaillerie\DTO\CreateProductDTO;
use Src\Domain\Quincaillerie\Entities\Product;
use Src\Domain\Quincaillerie\ValueObjects\ProductCode;
use Src\Domain\Quincaillerie\ValueObjects\TypeUnite;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Cas d'usage création produit - Module Quincaillerie.
 * Aucune dépendance au module Pharmacy.
 */
class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        $this->validateProductCode($dto->productCode, $dto->shopId);
        $this->validateCategory($dto->categoryId, $dto->shopId);

        $productCode = new ProductCode($dto->productCode);
        $price = new Money($dto->price, $dto->currency);
        $typeUnite = new TypeUnite($dto->typeUnite);
        $quantiteParUnite = max(1, $dto->quantiteParUnite);
        $initialStock = new Quantity(0);
        $minimumStock = new Quantity((float) $dto->minimumStock);

        $product = Product::create(
            $dto->shopId,
            $productCode,
            $dto->name,
            $dto->description ?? '',
            $price,
            $initialStock,
            $typeUnite,
            $quantiteParUnite,
            $dto->estDivisible,
            $dto->categoryId,
            $minimumStock
        );

        $this->productRepository->save($product);

        return $product;
    }

    private function validateProductCode(string $productCode, string $shopId): void
    {
        if ($this->productRepository->existsByCode($productCode, null, $shopId)) {
            throw new \InvalidArgumentException("Le code produit {$productCode} existe déjà.");
        }
    }

    private function validateCategory(string $categoryId, string $shopId): void
    {
        $category = $this->categoryRepository->findById($categoryId);
        if (!$category || $category->getShopId() !== $shopId) {
            throw new \InvalidArgumentException('Catégorie invalide.');
        }
    }
}
