<?php

namespace Src\Application\Ecommerce\UseCases;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Application\Ecommerce\DTO\CreateCustomerDTO;
use Src\Domain\Ecommerce\Entities\Customer;
use Src\Domain\Ecommerce\Repositories\CustomerRepositoryInterface;
use Src\Shared\ValueObjects\Money;

class CreateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(CreateCustomerDTO $dto): Customer
    {
        // Vérifier si le client existe déjà
        $existing = $this->customerRepository->findByEmail($dto->shopId, $dto->email);

        if ($existing) {
            throw new \InvalidArgumentException('Un client avec cet email existe déjà.');
        }

        $customer = new Customer(
            Uuid::uuid4()->toString(),
            $dto->shopId,
            $dto->email,
            $dto->firstName,
            $dto->lastName,
            $dto->phone,
            $dto->defaultShippingAddress,
            $dto->defaultBillingAddress,
            0,
            new Money(0, 'USD'),
            true,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $this->customerRepository->save($customer);

        return $customer;
    }
}
