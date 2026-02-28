<?php

namespace Src\Application\Quincaillerie\UseCases\Product;

use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Application\Quincaillerie\DTO\UpdateProductDTO;
use Src\Domain\Quincaillerie\ValueObjects\TypeUnite;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Cas d'usage mise à jour produit - Module Quincaillerie.
 */
class UpdateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(UpdateProductDTO $dto): void
    {
        $product = $this->productRepository->findById($dto->id);
        if (!$product) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        $category = $this->categoryRepository->findById($dto->categoryId);
        if (!$category || $category->getShopId() !== $product->getShopId()) {
            throw new \InvalidArgumentException('Catégorie invalide.');
        }

        $product->updateName($dto->name);
        $product->updateDescription($dto->description ?? '');
        $product->updatePrice(new Money($dto->price, $dto->currency));
        $product->updateCategory($dto->categoryId);
        $product->updateTypeUnite(new TypeUnite($dto->typeUnite));
        $product->updateQuantiteParUnite(max(1, $dto->quantiteParUnite));
        $product->setEstDivisible($dto->estDivisible);
        if ($dto->minimumStock !== null) {
            $product->updateMinimumStock(new Quantity((float) $dto->minimumStock));
        }

        $this->productRepository->update($product);
    }
}
