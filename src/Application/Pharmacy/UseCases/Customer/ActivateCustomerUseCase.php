<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Customer;

use Src\Domain\Pharmacy\Entities\Customer;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;
use RuntimeException;

/**
 * UseCase: ActivateCustomerUseCase
 *
 * Active un client.
 */
final class ActivateCustomerUseCase
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

        $customer->activate();

        $this->customerRepository->update($customer);

        return $customer;
    }
}
