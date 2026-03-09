<?php

namespace Src\Application\Ecommerce\UseCases;

use Src\Application\Ecommerce\Services\GenerateDownloadTokensService;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;

class UpdatePaymentStatusUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GenerateDownloadTokensService $generateDownloadTokensService
    ) {
    }

    public function execute(string $orderId, string $paymentStatus): Order
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \InvalidArgumentException('Commande introuvable.');
        }

        switch ($paymentStatus) {
            case Order::PAYMENT_STATUS_PAID:
                $order->markPaymentAsPaid();
                $this->generateDownloadTokensService->generateForOrder($orderId);
                break;
            case Order::PAYMENT_STATUS_FAILED:
                $order->markPaymentAsFailed();
                break;
            case Order::PAYMENT_STATUS_REFUNDED:
                $order->markPaymentAsRefunded();
                break;
            default:
                throw new \InvalidArgumentException("Statut de paiement invalide: {$paymentStatus}");
        }

        $this->orderRepository->save($order);

        return $order;
    }
}
