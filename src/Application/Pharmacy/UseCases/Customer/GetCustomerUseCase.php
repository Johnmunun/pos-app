<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Customer;

use Src\Domain\Pharmacy\Entities\Customer;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;

/**
 * UseCase: GetCustomerUseCase
 *
 * Récupère un client par son ID.
 */
final class GetCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(string $customerId): ?Customer
    {
        return $this->customerRepository->findById($customerId);
    }
}
