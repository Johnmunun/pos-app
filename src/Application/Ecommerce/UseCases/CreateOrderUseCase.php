<?php

namespace Src\Application\Ecommerce\UseCases;

use App\Services\EcommerceOrderPaidCustomerMailService;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Src\Application\Ecommerce\DTO\CreateOrderDTO;
use Src\Application\Ecommerce\DTO\OrderItemDTO;
use Src\Application\Ecommerce\Services\GenerateDownloadTokensService;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Domain\Ecommerce\Entities\OrderItem;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;
use Src\Domain\Ecommerce\Repositories\OrderItemRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface as GcProductRepositoryInterface;
use Src\Domain\Ecommerce\ValueObjects\OrderNumber;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

class CreateOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderItemRepositoryInterface $orderItemRepository,
        private readonly GcProductRepositoryInterface $gcProductRepository,
        private readonly GenerateDownloadTokensService $generateDownloadTokensService,
        private readonly EcommerceOrderPaidCustomerMailService $ecommerceOrderPaidCustomerMailService,
    ) {
    }

    public function execute(CreateOrderDTO $dto): Order
    {
        if (empty($dto->items)) {
            throw new \InvalidArgumentException('La commande doit contenir au moins un produit.');
        }

        return DB::transaction(function () use ($dto) {
            $currency = $dto->currency;
            $orderNumber = OrderNumber::generate();
            $initialPaymentStatus = $dto->paymentStatus ?: Order::PAYMENT_STATUS_PENDING;

            // Créer la commande
            $order = new Order(
                Uuid::uuid4()->toString(),
                $dto->shopId,
                $orderNumber->getValue(),
                Order::STATUS_PENDING,
                $dto->customerName,
                $dto->customerEmail,
                $dto->customerPhone,
                $dto->shippingAddress,
                $dto->billingAddress,
                new Money($dto->subtotalAmount, $currency),
                new Money($dto->shippingAmount, $currency),
                new Money($dto->taxAmount, $currency),
                new Money($dto->discountAmount, $currency),
                new Money($dto->subtotalAmount + $dto->shippingAmount + $dto->taxAmount - $dto->discountAmount, $currency),
                $currency,
                $dto->paymentMethod,
                $initialPaymentStatus,
                $dto->notes,
                $dto->createdBy,
                new DateTimeImmutable(),
                new DateTimeImmutable()
            );

            $this->orderRepository->save($order);

            // Créer les items de commande
            foreach ($dto->items as $itemDto) {
                $item = new OrderItem(
                    Uuid::uuid4()->toString(),
                    $order->getId(),
                    $itemDto->productId,
                    $itemDto->productName,
                    $itemDto->productSku,
                    new Quantity($itemDto->quantity),
                    new Money($itemDto->unitPrice, $currency),
                    new Money($itemDto->discountAmount, $currency),
                    new Money(($itemDto->unitPrice * $itemDto->quantity) - $itemDto->discountAmount, $currency),
                    $itemDto->productImageUrl,
                    new DateTimeImmutable(),
                    new DateTimeImmutable()
                );

                $this->orderItemRepository->save($item);

                // Déduire le stock du produit (gc_products)
                $product = $this->gcProductRepository->findById($itemDto->productId);
                if ($product && (string) $product->getShopId() === $dto->shopId) {
                    $product->removeStock(new Quantity($itemDto->quantity));
                    $this->gcProductRepository->update($product);
                }
            }

            // Si déjà payé (ex: paiement externe confirmé 200 OK), générer immédiatement les tokens de téléchargement
            if ($initialPaymentStatus === Order::PAYMENT_STATUS_PAID) {
                $this->generateDownloadTokensService->generateForOrder($order->getId());
                $this->ecommerceOrderPaidCustomerMailService->notifyOrderJustPaid($order);
            }

            return $order;
        });
    }
}
