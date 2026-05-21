<?php

namespace App\Services;

use App\Mail\EcommerceOrderCreatedMail;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;

final class EcommerceOrderCreatedMailService
{
    public function __construct(
        private readonly DynamicMailSettingsService $dynamicMailSettingsService,
        private readonly GetStoreSettingsUseCase $getStoreSettingsUseCase,
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

        foreach ($this->resolveShopRecipientEmails($order->getShopId(), $customerEmail) as $shopEmail) {
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
                    'email' => $shopEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolveShopRecipientEmails(string $shopId, string $customerEmail): array
    {
        $emails = [];
        $shop = Shop::query()->find($shopId);
        if ($shop !== null && trim((string) $shop->email) !== '') {
            $emails[] = trim((string) $shop->email);
        }

        try {
            $settings = $this->getStoreSettingsUseCase->execute($shopId);
            if ($settings !== null && trim((string) $settings->getEmail()) !== '') {
                $emails[] = trim((string) $settings->getEmail());
            }
        } catch (\Throwable $e) {
            Log::debug('EcommerceOrderCreatedMail: store settings email unavailable', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }

        $normalizedCustomer = strtolower($customerEmail);
        $unique = [];
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $key = strtolower($email);
            if ($key === $normalizedCustomer || isset($unique[$key])) {
                continue;
            }
            $unique[$key] = $email;
        }

        return array_values($unique);
    }
}
