<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\Customer;

/**
 * Repository Interface: CustomerRepositoryInterface
 *
 * Interface pour la persistence des clients.
 */
interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function update(Customer $customer): void;

    public function findById(string $id): ?Customer;

    public function findByNameInShop(string $name, int $shopId): ?Customer;

    /**
     * Retourne tous les clients d'une boutique.
     * @return Customer[]
     */
    public function findByShop(int $shopId): array;

    /**
     * Retourne tous les clients actifs d'une boutique.
     * @return Customer[]
     */
    public function findActiveByShop(int $shopId): array;

    /**
     * Recherche des clients par nom, téléphone ou email.
     * @return Customer[]
     */
    public function search(int $shopId, string $query): array;

    public function delete(string $id): void;
}
