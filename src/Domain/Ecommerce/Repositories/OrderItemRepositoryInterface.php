<?php

namespace Src\Domain\Ecommerce\Repositories;

use Src\Domain\Ecommerce\Entities\OrderItem;

interface OrderItemRepositoryInterface
{
    public function save(OrderItem $item): void;

    public function findById(string $id): ?OrderItem;

    /**
     * @return OrderItem[]
     */
    public function findByOrderId(string $orderId): array;

    public function deleteByOrderId(string $orderId): void;

    public function delete(string $id): void;
}
