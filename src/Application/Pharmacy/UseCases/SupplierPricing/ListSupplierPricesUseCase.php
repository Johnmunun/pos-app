<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\SupplierPricing;

use Src\Domain\Pharmacy\Entities\SupplierProductPrice;
use Src\Domain\Pharmacy\Repositories\SupplierProductPriceRepositoryInterface;

/**
 * UseCase: ListSupplierPricesUseCase
 *
 * Liste les prix d'un fournisseur.
 */
final class ListSupplierPricesUseCase
{
    public function __construct(
        private readonly SupplierProductPriceRepositoryInterface $priceRepository
    ) {
    }

    /**
     * @return SupplierProductPrice[]
     */
    public function execute(string $supplierId): array
    {
        return $this->priceRepository->findBySupplier($supplierId);
    }

    /**
     * @return SupplierProductPrice[]
     */
    public function getHistoryForProduct(string $supplierId, string $productId): array
    {
        return $this->priceRepository->findHistoryBySupplierAndProduct($supplierId, $productId);
    }
}
