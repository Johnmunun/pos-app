<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;

/**
 * Use case for getting batch summary statistics (for dashboard).
 */
final class GetBatchSummaryUseCase
{
    public function __construct(
        private ProductBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * Execute the use case.
     * 
     * @param string $shopId Shop ID
     * @param int $warningDays Days threshold for "expiring soon" (default 30)
     * 
     * @return array{
     *     expired_count: int,
     *     expiring_soon_count: int,
     *     total_batches: int
     * }
     */
    public function execute(string $shopId, int $warningDays = 30): array
    {
        $expiredCount = $this->batchRepository->countExpiredByShop($shopId);
        $expiringSoonCount = $this->batchRepository->countExpiringByShop($shopId, $warningDays);
        
        $allBatches = $this->batchRepository->findByShop($shopId);
        $totalBatches = count($allBatches);

        return [
            'expired_count' => $expiredCount,
            'expiring_soon_count' => $expiringSoonCount,
            'total_batches' => $totalBatches,
        ];
    }
}
