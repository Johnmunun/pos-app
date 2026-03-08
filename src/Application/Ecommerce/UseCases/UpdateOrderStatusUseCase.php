<?php

namespace Src\Application\Ecommerce\UseCases;

use Src\Domain\Ecommerce\Entities\Order;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;

class UpdateOrderStatusUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(string $orderId, string $newStatus): Order
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \InvalidArgumentException('Commande introuvable.');
        }

        switch ($newStatus) {
            case Order::STATUS_CONFIRMED:
                $order->confirm();
                break;
            case Order::STATUS_PROCESSING:
                $order->markAsProcessing();
                break;
            case Order::STATUS_SHIPPED:
                $order->markAsShipped();
                break;
            case Order::STATUS_DELIVERED:
                $order->markAsDelivered();
                break;
            case Order::STATUS_CANCELLED:
                $order->cancel();
                break;
            default:
                throw new \InvalidArgumentException("Statut invalide: {$newStatus}");
        }

        $this->orderRepository->save($order);

        return $order;
    }
}
