<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Supplier;

use Src\Domain\Pharmacy\Entities\Supplier;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;
use RuntimeException;

/**
 * UseCase: ActivateSupplierUseCase
 *
 * Active un fournisseur.
 */
final class ActivateSupplierUseCase
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

        $supplier->activate();
        $this->supplierRepository->update($supplier);

        return $supplier;
    }
}
