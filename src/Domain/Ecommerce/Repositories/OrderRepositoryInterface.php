<?php

namespace Src\Domain\Ecommerce\Repositories;

use DateTimeImmutable;
use Src\Domain\Ecommerce\Entities\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(string $id): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    /**
     * @return Order[]
     */
    public function findByShop(string $shopId, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null, ?string $status = null): array;

    /**
     * @return Order[]
     */
    public function findByCustomerEmail(string $shopId, string $email): array;

    public function countByShop(string $shopId, ?string $status = null): int;
}
