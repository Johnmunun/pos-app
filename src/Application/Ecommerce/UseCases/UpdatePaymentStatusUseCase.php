<?php

namespace Src\Application\Ecommerce\UseCases;

use App\Services\EcommerceOrderPaidCustomerMailService;
use Src\Application\Ecommerce\Services\GenerateDownloadTokensService;
use Src\Application\Referral\Services\ReferralService;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;

class UpdatePaymentStatusUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GenerateDownloadTokensService $generateDownloadTokensService,
        private readonly ReferralService $referralService,
        private readonly EcommerceOrderPaidCustomerMailService $ecommerceOrderPaidCustomerMailService,
    ) {
    }

    public function execute(string $orderId, string $paymentStatus): Order
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \InvalidArgumentException('Commande introuvable.');
        }

        $previousPaymentStatus = $order->getPaymentStatus();

        switch ($paymentStatus) {
            case Order::PAYMENT_STATUS_PAID:
                $order->markPaymentAsPaid();
                $this->generateDownloadTokensService->generateForOrder($orderId);
                // Enregistrer la transaction pour le système de parrainage (e-commerce)
                $shopId = (int) $order->getShopId();
                $buyerUserId = $order->getCreatedBy();
                if ($buyerUserId !== null) {
                    $this->referralService->recordTransaction(
                        $shopId,
                        $buyerUserId,
                        $order->getTotal()->getAmount(),
                        'ecommerce_order',
                        $order->getId()
                    );
                }
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

        if (
            $paymentStatus === Order::PAYMENT_STATUS_PAID
            && $previousPaymentStatus !== Order::PAYMENT_STATUS_PAID
        ) {
            $this->ecommerceOrderPaidCustomerMailService->notifyOrderJustPaid($order);
        }

        return $order;
    }
}
