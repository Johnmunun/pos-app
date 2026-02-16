<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\ProductBatch;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;

/**
 * Use case for getting expired batches.
 */
final class GetExpiredBatchesUseCase
{
    public function __construct(
        private ProductBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * Execute the use case.
     * 
     * @param string $shopId Shop ID
     * @param DateTimeImmutable|null $asOf Reference date (default now)
     * 
     * @return ProductBatch[]
     */
    public function execute(string $shopId, ?DateTimeImmutable $asOf = null): array
    {
        return $this->batchRepository->findExpiredByShop($shopId, $asOf);
    }
}
