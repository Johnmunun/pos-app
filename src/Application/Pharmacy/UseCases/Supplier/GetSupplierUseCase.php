<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Supplier;

use Src\Domain\Pharmacy\Entities\Supplier;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;
use RuntimeException;

/**
 * UseCase: GetSupplierUseCase
 *
 * Récupère un fournisseur par son ID.
 */
final class GetSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    public function execute(string $id): Supplier
    {
        $supplier = $this->supplierRepository->findById($id);
        if ($supplier === null) {
            throw new RuntimeException('Fournisseur introuvable.');
        }

        return $supplier;
    }
}
