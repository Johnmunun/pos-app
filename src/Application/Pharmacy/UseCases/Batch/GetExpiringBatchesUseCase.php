<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\ProductBatch;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;

/**
 * Use case for getting batches that are expiring soon.
 */
final class GetExpiringBatchesUseCase
{
    private const DEFAULT_WARNING_DAYS = 30;

    public function __construct(
        private ProductBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * Execute the use case.
     * 
     * @param string $shopId Shop ID
     * @param int $days Number of days to look ahead (default 30)
     * @param DateTimeImmutable|null $asOf Reference date (default now)
     * 
     * @return ProductBatch[]
     */
    public function execute(string $shopId, int $days = self::DEFAULT_WARNING_DAYS, ?DateTimeImmutable $asOf = null): array
    {
        return $this->batchRepository->findExpiringByShop($shopId, $days, $asOf);
    }
}
