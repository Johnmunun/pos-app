<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use Src\Application\Pharmacy\DTO\CreateBatchDTO;
use Src\Domain\Pharmacy\Entities\ProductBatch;

/**
 * Interface for adding batches.
 * 
 * Allows different implementations (real batch creation or no-op for Hardware).
 */
interface AddBatchUseCaseInterface
{
    /**
     * Execute the use case to add a batch.
     * 
     * @param CreateBatchDTO $dto DTO containing batch information
     * @return ProductBatch The created or updated batch
     */
    public function execute(CreateBatchDTO $dto): ProductBatch;
}
