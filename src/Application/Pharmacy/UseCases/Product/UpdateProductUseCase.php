<?php

namespace Src\Application\Pharmacy\UseCases\Product;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\UpdateProductDTO;
use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Pharmacy\ValueObjects\MedicineType;
use Src\Domain\Pharmacy\ValueObjects\Dosage;
use Src\Domain\Pharmacy\ValueObjects\TypeUnite;
use Src\Shared\ValueObjects\Money;

class UpdateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(string $productId, UpdateProductDTO $dto): Product
    {
        // Find existing product
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new \InvalidArgumentException("Product not found");
        }

        // Validate business rules uniquement si les champs sont fournis
        if ($dto->productCode !== null) {
            $this->validateProductCode($dto->productCode, $productId);
        }
        if ($dto->categoryId !== null) {
            $this->validateCategory($dto->categoryId, $dto->shopId);
        }

        // Update product properties
        if ($dto->name !== null) {
            $product->updateName($dto->name);
        }

        if ($dto->productCode !== null) {
            $product->updateCode(new ProductCode($dto->productCode));
        }

        if ($dto->description !== null) {
            $product->updateDescription($dto->description);
        }

        if ($dto->categoryId !== null) {
            $product->updateCategory($dto->categoryId);
        }

        if ($dto->price !== null) {
            $price = new Money($dto->price, $dto->currency ?? $product->getPrice()->getCurrency());
            $product->updatePrice($price);
        }

        if ($dto->medicineType !== null) {
            $medicineType = $dto->medicineType ? new MedicineType($dto->medicineType) : null;
            $product->updateMedicineType($medicineType);
        }

        if ($dto->dosage !== null) {
            $dosage = $dto->dosage ? new Dosage($dto->dosage) : null;
            $product->updateDosage($dosage);
        }

        if ($dto->prescriptionRequired !== null) {
            // L'entité de domaine expose setRequiresPrescription()
            $product->setRequiresPrescription($dto->prescriptionRequired);
        }

        if ($dto->isActive !== null) {
            if ($dto->isActive) {
                $product->activate();
            } else {
                $product->deactivate();
            }
        }

        if ($dto->typeUnite !== null) {
            $product->updateTypeUnite(new TypeUnite($dto->typeUnite));
        }
        if ($dto->quantiteParUnite !== null) {
            $product->updateQuantiteParUnite(max(1, $dto->quantiteParUnite));
        }
        if ($dto->estDivisible !== null) {
            $product->setEstDivisible($dto->estDivisible);
        }

        // Save updated product
        $this->productRepository->update($product);

        return $product;
    }

    private function validateProductCode(string $productCode, string $excludeId): void
    {
        if ($this->productRepository->existsByCode($productCode, $excludeId)) {
            throw new \InvalidArgumentException("Product code {$productCode} already exists");
        }
    }

    private function validateCategory(string $categoryId, string $shopId): void
    {
        $category = $this->categoryRepository->findById($categoryId);
        if (!$category || $category->getShopId() !== $shopId) {
            throw new \InvalidArgumentException('Invalid category');
        }
    }
}