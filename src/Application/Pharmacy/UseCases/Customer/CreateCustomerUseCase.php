<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Customer;

use Src\Application\Pharmacy\DTO\CreateCustomerDTO;
use Src\Domain\Pharmacy\Entities\Customer;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\CustomerType;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;
use InvalidArgumentException;

/**
 * UseCase: CreateCustomerUseCase
 *
 * Crée un nouveau client.
 */
final class CreateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(CreateCustomerDTO $dto): Customer
    {
        // Valider le nom
        if (empty(trim($dto->name))) {
            throw new InvalidArgumentException('Le nom du client est obligatoire.');
        }

        // Vérifier les doublons sur le nom dans la même boutique
        $existingCustomer = $this->customerRepository->findByNameInShop($dto->name, $dto->shopId);
        if ($existingCustomer !== null) {
            throw new InvalidArgumentException(
                sprintf('Un client avec le nom "%s" existe déjà.', $dto->name)
            );
        }

        // Créer les Value Objects
        $phone = new SupplierPhone($dto->phone);
        $email = new SupplierEmail($dto->email);
        $customerType = new CustomerType($dto->customerType);

        // Créer l'entité
        $customer = Customer::create(
            $dto->shopId,
            $dto->name,
            $phone,
            $email,
            $dto->address,
            $customerType,
            $dto->taxNumber,
            $dto->creditLimit
        );

        // Persister
        $this->customerRepository->save($customer);

        return $customer;
    }
}
