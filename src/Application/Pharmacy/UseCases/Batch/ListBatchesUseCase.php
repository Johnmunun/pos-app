<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\ProductBatch;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;

/**
 * Use case for listing batches with filters.
 */
final class ListBatchesUseCase
{
    public function __construct(
        private ProductBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * Execute the use case.
     * 
     * @param array{
     *     shop_id: string,
     *     product_id?: string,
     *     status?: string,
     *     search?: string,
     *     from_date?: string|DateTimeImmutable|null,
     *     to_date?: string|DateTimeImmutable|null
     * } $filters
     * @param int $limit
     * @param int $offset
     * 
     * @return array{
     *     batches: ProductBatch[],
     *     total: int
     * }
     */
    public function execute(array $filters, int $limit = 50, int $offset = 0): array
    {
        // Convert date strings to DateTimeImmutable
        if (isset($filters['from_date']) && is_string($filters['from_date']) && $filters['from_date'] !== '') {
            $filters['from_date'] = new DateTimeImmutable($filters['from_date']);
        }
        if (isset($filters['to_date']) && is_string($filters['to_date']) && $filters['to_date'] !== '') {
            $filters['to_date'] = new DateTimeImmutable($filters['to_date']);
        }

        $batches = $this->batchRepository->search($filters, $limit, $offset);
        $total = $this->batchRepository->countByFilters($filters);

        return [
            'batches' => $batches,
            'total' => $total,
        ];
    }
}
