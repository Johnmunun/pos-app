<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\SupplierPricing;

use Src\Domain\Pharmacy\Entities\SupplierProductPrice;
use Src\Domain\Pharmacy\Repositories\SupplierProductPriceRepositoryInterface;

/**
 * UseCase: GetSupplierProductPriceUseCase
 *
 * Récupère le prix d'un produit chez un fournisseur.
 */
final class GetSupplierProductPriceUseCase
{
    public function __construct(
        private readonly SupplierProductPriceRepositoryInterface $priceRepository
    ) {
    }

    public function execute(string $supplierId, string $productId): ?SupplierProductPrice
    {
        return $this->priceRepository->findBySupplierAndProduct($supplierId, $productId);
    }

    /**
     * Récupère tous les prix pour un fournisseur.
     * @return SupplierProductPrice[]
     */
    public function getBySupplier(string $supplierId): array
    {
        return $this->priceRepository->findBySupplier($supplierId);
    }

    /**
     * Récupère tous les prix pour un produit.
     * @return SupplierProductPrice[]
     */
    public function getByProduct(string $productId): array
    {
        return $this->priceRepository->findByProduct($productId);
    }
}
