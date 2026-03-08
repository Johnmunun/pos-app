<?php

namespace Src\Domain\Ecommerce\Repositories;

use Src\Domain\Ecommerce\Entities\Customer;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function findById(string $id): ?Customer;

    public function findByEmail(string $shopId, string $email): ?Customer;

    /**
     * @return Customer[]
     */
    public function findByShop(string $shopId, bool $activeOnly = false): array;

    public function delete(string $id): void;
}
