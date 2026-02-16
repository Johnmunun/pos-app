<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Customer;

use Src\Domain\Pharmacy\Entities\Customer;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;

/**
 * UseCase: ListCustomersUseCase
 *
 * Liste les clients d'une boutique.
 */
final class ListCustomersUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * @return Customer[]
     */
    public function execute(int $shopId): array
    {
        return $this->customerRepository->findByShop($shopId);
    }

    /**
     * @return Customer[]
     */
    public function listActive(int $shopId): array
    {
        return $this->customerRepository->findActiveByShop($shopId);
    }

    /**
     * @return Customer[]
     */
    public function search(int $shopId, string $query): array
    {
        return $this->customerRepository->search($shopId, $query);
    }
}
