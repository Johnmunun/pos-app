<?php

namespace Src\Infrastructure\Ecommerce\Persistence;

use DateTimeImmutable;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Shared\ValueObjects\Money;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        OrderModel::updateOrCreate(
            ['id' => $order->getId()],
            [
                'shop_id' => $order->getShopId(),
                'order_number' => $order->getOrderNumber(),
                'status' => $order->getStatus(),
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_phone' => $order->getCustomerPhone(),
                'shipping_address' => $order->getShippingAddress(),
                'billing_address' => $order->getBillingAddress(),
                'subtotal_amount' => $order->getSubtotal()->getAmount(),
                'shipping_amount' => $order->getShippingAmount()->getAmount(),
                'tax_amount' => $order->getTaxAmount()->getAmount(),
                'discount_amount' => $order->getDiscountAmount()->getAmount(),
                'total_amount' => $order->getTotal()->getAmount(),
                'currency' => $order->getCurrency(),
                'payment_method' => $order->getPaymentMethod(),
                'payment_status' => $order->getPaymentStatus(),
                'notes' => $order->getNotes(),
                'confirmed_at' => $order->getConfirmedAt(),
                'shipped_at' => $order->getShippedAt(),
                'delivered_at' => $order->getDeliveredAt(),
                'cancelled_at' => $order->getCancelledAt(),
                'created_by' => $order->getCreatedBy(),
            ]
        );
    }

    public function findById(string $id): ?Order
    {
        $model = OrderModel::with('items')->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $model = OrderModel::with('items')->where('order_number', $orderNumber)->first();

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @return Order[]
     */
    public function findByShop(string $shopId, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null, ?string $status = null): array
    {
        $query = OrderModel::query()->where('shop_id', $shopId);

        if ($from) {
            $query->where('created_at', '>=', $from->setTime(0, 0, 0));
        }

        if ($to) {
            $query->where('created_at', '<=', $to->setTime(23, 59, 59));
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (OrderModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    /**
     * @return Order[]
     */
    public function findByCustomerEmail(string $shopId, string $email): array
    {
        return OrderModel::query()
            ->where('shop_id', $shopId)
            ->where('customer_email', $email)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (OrderModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function countByShop(string $shopId, ?string $status = null): int
    {
        $query = OrderModel::query()->where('shop_id', $shopId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    private function mapToEntity(OrderModel $model): Order
    {
        $currency = $model->currency ?? 'USD';

        return new Order(
            $model->id,
            (string) $model->shop_id,
            $model->order_number,
            $model->status,
            $model->customer_name,
            $model->customer_email,
            $model->customer_phone,
            $model->shipping_address,
            $model->billing_address,
            new Money((float) $model->subtotal_amount, $currency),
            new Money((float) $model->shipping_amount, $currency),
            new Money((float) $model->tax_amount, $currency),
            new Money((float) $model->discount_amount, $currency),
            new Money((float) $model->total_amount, $currency),
            $currency,
            $model->payment_method,
            $model->payment_status,
            $model->notes,
            $model->created_by,
            $model->created_at ? DateTimeImmutable::createFromMutable($model->created_at) : new DateTimeImmutable(),
            $model->updated_at ? DateTimeImmutable::createFromMutable($model->updated_at) : new DateTimeImmutable(),
            $model->confirmed_at ? DateTimeImmutable::createFromMutable($model->confirmed_at) : null,
            $model->shipped_at ? DateTimeImmutable::createFromMutable($model->shipped_at) : null,
            $model->delivered_at ? DateTimeImmutable::createFromMutable($model->delivered_at) : null,
            $model->cancelled_at ? DateTimeImmutable::createFromMutable($model->cancelled_at) : null
        );
    }
}
