<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Customer;

use Src\Application\Pharmacy\DTO\UpdateCustomerDTO;
use Src\Domain\Pharmacy\Entities\Customer;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\CustomerType;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;
use RuntimeException;

/**
 * UseCase: UpdateCustomerUseCase
 *
 * Met à jour un client existant.
 */
final class UpdateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(UpdateCustomerDTO $dto): Customer
    {
        $customer = $this->customerRepository->findById($dto->id);

        if ($customer === null) {
            throw new RuntimeException(
                sprintf('Client introuvable avec l\'ID: %s', $dto->id)
            );
        }

        // Préparer les Value Objects si fournis
        $phone = $dto->phone !== null ? new SupplierPhone($dto->phone) : null;
        $email = $dto->email !== null ? new SupplierEmail($dto->email) : null;
        $customerType = $dto->customerType !== null ? new CustomerType($dto->customerType) : null;

        // Mettre à jour
        $customer->update(
            $dto->name,
            $phone,
            $email,
            $dto->address,
            $customerType,
            $dto->taxNumber,
            $dto->creditLimit
        );

        // Persister
        $this->customerRepository->update($customer);

        return $customer;
    }
}
