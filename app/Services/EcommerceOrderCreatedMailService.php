<?php

namespace App\Services;

use App\Mail\EcommerceOrderCreatedMail;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;

final class EcommerceOrderCreatedMailService
{
    public function __construct(
        private readonly DynamicMailSettingsService $dynamicMailSettingsService
    ) {
    }

    public function notifyOrderCreated(Order $order, ?string $note = null): void
    {
        $this->dynamicMailSettingsService->applyFromStorage();

        $items = OrderItemModel::query()
            ->where('order_id', $order->getId())
            ->orderBy('id')
            ->get();

        $lines = [];
        foreach ($items as $item) {
            $lines[] = [
                'name' => (string) $item->product_name,
                'quantity' => (float) $item->quantity,
                'line_total' => (float) $item->subtotal,
            ];
        }

        $digitalDownloads = [];
        foreach ($items as $item) {
            if (!$item->is_digital || empty($item->download_token)) {
                continue;
            }
            $digitalDownloads[] = [
                'name' => (string) $item->product_name,
                'url' => route('ecommerce.download', ['token' => $item->download_token]),
            ];
        }

        $customerEmail = trim((string) $order->getCustomerEmail());
        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($customerEmail)->send(new EcommerceOrderCreatedMail(
                    recipientLabel: 'client',
                    customerName: $order->getCustomerName(),
                    orderNumber: $order->getOrderNumber(),
                    totalAmount: $order->getTotal()->getAmount(),
                    currency: $order->getTotal()->getCurrency(),
                    paymentStatus: $order->getPaymentStatus(),
                    lines: $lines,
                    digitalDownloads: $digitalDownloads,
                    note: $note
                ));
            } catch (\Throwable $e) {
                Log::warning('EcommerceOrderCreatedMail customer failed', [
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $shopEmail = trim((string) (Shop::query()->find($order->getShopId())?->email ?? ''));
        if (
            $shopEmail !== ''
            && filter_var($shopEmail, FILTER_VALIDATE_EMAIL)
            && strcasecmp($shopEmail, $customerEmail) !== 0
        ) {
            try {
                Mail::to($shopEmail)->send(new EcommerceOrderCreatedMail(
                    recipientLabel: 'boutique',
                    customerName: $order->getCustomerName(),
                    orderNumber: $order->getOrderNumber(),
                    totalAmount: $order->getTotal()->getAmount(),
                    currency: $order->getTotal()->getCurrency(),
                    paymentStatus: $order->getPaymentStatus(),
                    lines: $lines,
                    digitalDownloads: $digitalDownloads,
                    note: $note
                ));
            } catch (\Throwable $e) {
                Log::warning('EcommerceOrderCreatedMail shop failed', [
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
