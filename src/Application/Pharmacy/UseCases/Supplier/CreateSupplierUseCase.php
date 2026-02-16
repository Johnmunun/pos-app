<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Supplier;

use Src\Application\Pharmacy\DTO\CreateSupplierDTO;
use Src\Domain\Pharmacy\Entities\Supplier;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;
use InvalidArgumentException;

/**
 * UseCase: CreateSupplierUseCase
 *
 * Crée un nouveau fournisseur.
 */
final class CreateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    public function execute(CreateSupplierDTO $dto): Supplier
    {
        // Valider le nom
        if (empty(trim($dto->name))) {
            throw new InvalidArgumentException('Le nom du fournisseur est obligatoire.');
        }

        // Vérifier les doublons sur le nom dans la même boutique
        $existingSupplier = $this->supplierRepository->findByNameInShop($dto->name, $dto->shopId);
        if ($existingSupplier !== null) {
            throw new InvalidArgumentException(
                sprintf('Un fournisseur avec le nom "%s" existe déjà.', $dto->name)
            );
        }

        // Créer les Value Objects
        $phone = new SupplierPhone($dto->phone);
        $email = new SupplierEmail($dto->email);

        // Créer l'entité
        $supplier = Supplier::create(
            $dto->shopId,
            $dto->name,
            $dto->contactPerson,
            $phone,
            $email,
            $dto->address
        );

        // Persister
        $this->supplierRepository->save($supplier);

        return $supplier;
    }
}
