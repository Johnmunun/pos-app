<?php

namespace Src\Application\Pharmacy\UseCases\Product;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\UpdateProductDTO;
use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Pharmacy\ValueObjects\MedicineType;
use Src\Domain\Pharmacy\ValueObjects\Dosage;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

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

        // Validate business rules
        $this->validateProductCode($dto->productCode, $productId);
        $this->validateCategory($dto->categoryId, $dto->shopId);

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

        if ($dto->cost !== null) {
            $cost = new Money($dto->cost, $dto->currency ?? $product->getPrice()->getCurrency());
            $product->updateCost($cost);
        }

        if ($dto->minimumStock !== null) {
            $product->updateMinimumStock(new Quantity($dto->minimumStock));
        }

        if ($dto->unit !== null) {
            $product->updateUnit($dto->unit);
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
            $product->updatePrescriptionRequirement($dto->prescriptionRequired);
        }

        if ($dto->manufacturer !== null) {
            $product->updateManufacturer($dto->manufacturer);
        }

        if ($dto->supplierId !== null) {
            $product->updateSupplier($dto->supplierId);
        }

        if ($dto->isActive !== null) {
            if ($dto->isActive) {
                $product->activate();
            } else {
                $product->deactivate();
            }
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