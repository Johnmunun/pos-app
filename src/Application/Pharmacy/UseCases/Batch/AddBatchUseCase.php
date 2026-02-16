<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Src\Application\Pharmacy\DTO\CreateBatchDTO;
use Src\Domain\Pharmacy\Entities\ProductBatch;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\BatchNumber;
use Src\Domain\Pharmacy\ValueObjects\ExpirationDate;
use Src\Shared\ValueObjects\Quantity;

/**
 * Use case for adding a new batch or increasing quantity of existing batch.
 */
final class AddBatchUseCase
{
    public function __construct(
        private ProductBatchRepositoryInterface $batchRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Execute the use case.
     * 
     * If a batch with the same batch number exists for the product,
     * the quantity is added to the existing batch.
     * Otherwise, a new batch is created.
     * 
     * @throws InvalidArgumentException if validation fails
     */
    public function execute(CreateBatchDTO $dto): ProductBatch
    {
        // Validate product exists
        $product = $this->productRepository->findById($dto->productId);
        if (!$product) {
            throw new InvalidArgumentException('Produit non trouvé.');
        }

        if ($dto->quantity <= 0) {
            throw new InvalidArgumentException('La quantité doit être supérieure à zéro.');
        }

        $batchNumber = new BatchNumber($dto->batchNumber);
        $expirationDate = ExpirationDate::fromString($dto->expirationDate->format('Y-m-d'));

        return DB::transaction(function () use ($dto, $batchNumber, $expirationDate) {
            // Check if batch already exists
            $existingBatch = $this->batchRepository->findByProductAndBatchNumber(
                $dto->productId,
                $dto->batchNumber
            );

            if ($existingBatch) {
                // Add to existing batch
                $existingBatch->increaseQuantity($dto->quantity);
                $this->batchRepository->update($existingBatch);
                return $existingBatch;
            }

            // Create new batch
            $batch = ProductBatch::create(
                id: Uuid::uuid4()->toString(),
                shopId: $dto->shopId,
                productId: $dto->productId,
                batchNumber: $batchNumber,
                quantity: new Quantity($dto->quantity),
                expirationDate: $expirationDate,
                purchaseOrderId: $dto->purchaseOrderId,
                purchaseOrderLineId: $dto->purchaseOrderLineId
            );

            $this->batchRepository->save($batch);

            return $batch;
        });
    }
}
