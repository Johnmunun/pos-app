<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Supplier;

use Src\Domain\Pharmacy\Entities\Supplier;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;

/**
 * UseCase: ListSuppliersUseCase
 *
 * Liste les fournisseurs d'une boutique.
 */
final class ListSuppliersUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    /**
     * @return Supplier[]
     */
    public function execute(int $shopId, bool $activeOnly = false): array
    {
        if ($activeOnly) {
            return $this->supplierRepository->findActiveByShop($shopId);
        }

        return $this->supplierRepository->findByShop($shopId);
    }
}
