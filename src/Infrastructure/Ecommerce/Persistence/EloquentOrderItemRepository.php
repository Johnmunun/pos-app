<?php

namespace Src\Infrastructure\Ecommerce\Persistence;

use Src\Domain\Ecommerce\Entities\OrderItem;
use Src\Domain\Ecommerce\Repositories\OrderItemRepositoryInterface;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

class EloquentOrderItemRepository implements OrderItemRepositoryInterface
{
    public function save(OrderItem $item): void
    {
        OrderItemModel::updateOrCreate(
            ['id' => $item->getId()],
            [
                'order_id' => $item->getOrderId(),
                'product_id' => $item->getProductId(),
                'product_name' => $item->getProductName(),
                'product_sku' => $item->getProductSku(),
                'quantity' => $item->getQuantity()->getValue(),
                'unit_price' => $item->getUnitPrice()->getAmount(),
                'discount_amount' => $item->getDiscountAmount()->getAmount(),
                'subtotal' => $item->getSubtotal()->getAmount(),
                'product_image_url' => $item->getProductImageUrl(),
            ]
        );
    }

    public function findById(string $id): ?OrderItem
    {
        $model = OrderItemModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @return OrderItem[]
     */
    public function findByOrderId(string $orderId): array
    {
        return OrderItemModel::where('order_id', $orderId)
            ->get()
            ->map(fn (OrderItemModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function deleteByOrderId(string $orderId): void
    {
        OrderItemModel::where('order_id', $orderId)->delete();
    }

    public function delete(string $id): void
    {
        OrderItemModel::where('id', $id)->delete();
    }

    private function mapToEntity(OrderItemModel $model): OrderItem
    {
        // Charger l'order pour obtenir la currency
        $order = $model->order ?? OrderModel::find($model->order_id);
        $currency = $order?->currency ?? 'USD';

        return new OrderItem(
            $model->id,
            $model->order_id,
            $model->product_id,
            $model->product_name,
            $model->product_sku,
            new Quantity((float) $model->quantity),
            new Money((float) $model->unit_price, $currency),
            new Money((float) $model->discount_amount, $currency),
            new Money((float) $model->subtotal, $currency),
            $model->product_image_url,
            $model->created_at ? \DateTimeImmutable::createFromMutable($model->created_at) : new \DateTimeImmutable(),
            $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : new \DateTimeImmutable()
        );
    }
}
