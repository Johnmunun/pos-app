<?php

declare(strict_types=1);

namespace Src\Application\Quincaillerie\UseCases\Customer;

use Src\Domain\Quincaillerie\Entities\Customer;
use Src\Domain\Quincaillerie\Repositories\CustomerRepositoryInterface;
use RuntimeException;

/**
 * UseCase: DeactivateCustomerUseCase
 *
 * Désactive un client.
 */
final class DeactivateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(string $customerId): Customer
    {
        $customer = $this->customerRepository->findById($customerId);

        if ($customer === null) {
            throw new RuntimeException(
                sprintf('Client introuvable avec l\'ID: %s', $customerId)
            );
        }

        $customer->deactivate();

        $this->customerRepository->update($customer);

        return $customer;
    }
}
