<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Supplier;

use Src\Application\Pharmacy\DTO\UpdateSupplierDTO;
use Src\Domain\Pharmacy\Entities\Supplier;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;
use InvalidArgumentException;
use RuntimeException;

/**
 * UseCase: UpdateSupplierUseCase
 *
 * Met à jour un fournisseur existant.
 */
final class UpdateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    public function execute(UpdateSupplierDTO $dto): Supplier
    {
        // Récupérer le fournisseur
        $supplier = $this->supplierRepository->findById($dto->id);
        if ($supplier === null) {
            throw new RuntimeException('Fournisseur introuvable.');
        }

        // Vérifier les doublons si le nom change
        if ($dto->name !== null && strtolower(trim($dto->name)) !== strtolower($supplier->getName())) {
            $existingSupplier = $this->supplierRepository->findByNameInShop($dto->name, $supplier->getShopId());
            if ($existingSupplier !== null && $existingSupplier->getId() !== $supplier->getId()) {
                throw new InvalidArgumentException(
                    sprintf('Un fournisseur avec le nom "%s" existe déjà.', $dto->name)
                );
            }
        }

        // Créer les Value Objects si fournis
        $phone = $dto->phone !== null ? new SupplierPhone($dto->phone) : null;
        $email = $dto->email !== null ? new SupplierEmail($dto->email) : null;

        // Mettre à jour l'entité
        $supplier->update(
            $dto->name,
            $dto->contactPerson,
            $phone,
            $email,
            $dto->address
        );

        // Persister
        $this->supplierRepository->update($supplier);

        return $supplier;
    }
}
