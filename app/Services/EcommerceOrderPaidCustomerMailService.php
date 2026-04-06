<?php

namespace App\Services;

use App\Mail\EcommerceOrderPaidCustomerMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;

/**
 * Envoie un e-mail au client lorsque le paiement de la commande est confirmé (ex. retour FusionPay / webhook).
 * Utilise la même logique SMTP que le reste de l’application (applyFromStorage puis .env).
 */
final class EcommerceOrderPaidCustomerMailService
{
    public function __construct(
        private readonly DynamicMailSettingsService $dynamicMailSettingsService
    ) {
    }

    public function notifyOrderJustPaid(Order $order): void
    {
        $email = trim($order->getCustomerEmail());
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->dynamicMailSettingsService->applyFromStorage();

        $items = OrderItemModel::query()
            ->where('order_id', $order->getId())
            ->orderBy('id')
            ->get();

        $lines = [];
        foreach ($items as $item) {
            $lines[] = [
                'name' => (string) $item->product_name,
                'sku' => $item->product_sku,
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

        $firstToken = $items->first(fn ($i) => $i->is_digital && !empty($i->download_token))?->download_token;
        $successPageUrl = $firstToken ? route('ecommerce.payment.success', ['token' => $firstToken]) : null;

        try {
            Mail::to($email)->send(new EcommerceOrderPaidCustomerMail(
                customerName: $order->getCustomerName(),
                orderNumber: $order->getOrderNumber(),
                totalAmount: $order->getTotal()->getAmount(),
                currency: $order->getTotal()->getCurrency(),
                lines: $lines,
                digitalDownloads: $digitalDownloads,
                successPageUrl: $successPageUrl,
            ));
        } catch (\Throwable $e) {
            Log::warning('EcommerceOrderPaidCustomerMail failed', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
