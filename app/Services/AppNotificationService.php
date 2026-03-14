<?php

namespace App\Services;

use App\Events\AdminNotificationBroadcast;
use App\Models\AppNotification;

class AppNotificationService
{
    public function __construct(
        private readonly WebPushService $webPushService
    ) {
    }

    /**
     * Notifie les admins qu'un produit a été créé (tous modules).
     */
    public function notifyProductCreated(
        string $module,
        string $productName,
        string $productCodeOrSku,
        ?string $shopId = null,
        ?int $tenantId = null
    ): AppNotification {
        $title = 'Nouveau produit créé';
        $body = sprintf(
            '[%s] %s (%s) a été ajouté.',
            $module,
            $productName,
            $productCodeOrSku
        );

        $notification = AppNotification::create([
            'user_id' => auth()->id(),
            'tenant_id' => $tenantId,
            'type' => 'product.created',
            'title' => $title,
            'body' => $body,
            'data' => [
                'module' => $module,
                'product_name' => $productName,
                'product_code_or_sku' => $productCodeOrSku,
                'shop_id' => $shopId,
                'tenant_id' => $tenantId,
            ],
        ]);

        $this->sendToAdminsSafe($notification);
        AdminNotificationBroadcast::dispatch($notification);

        return $notification;
    }

    /**
     * Notifie les admins qu'une vente a été enregistrée avec succès (200 OK).
     */
    public function notifySaleCompleted(
        float $totalAmount,
        string $currency,
        ?string $saleId = null,
        ?string $customerName = null,
        ?int $tenantId = null
    ): AppNotification {
        $title = 'Vente enregistrée';
        $body = sprintf(
            'Vente de %s %s enregistrée avec succès.',
            number_format((float) $totalAmount, 2, ',', ' '),
            $currency
        );
        if ($customerName) {
            $body .= ' Client : ' . $customerName;
        }

        $notification = AppNotification::create([
            'user_id' => auth()->id(),
            'tenant_id' => $tenantId,
            'type' => 'sale.completed',
            'title' => $title,
            'body' => $body,
            'data' => [
                'sale_id' => $saleId,
                'total_amount' => $totalAmount,
                'currency' => $currency,
                'customer_name' => $customerName,
                'tenant_id' => $tenantId,
            ],
        ]);

        $this->sendToAdminsSafe($notification);
        AdminNotificationBroadcast::dispatch($notification);

        return $notification;
    }

    /**
     * Notifie un utilisateur que son compte a été activé (notification + push sur son téléphone).
     */
    public function notifyAccountActivated(\App\Models\User $user): AppNotification
    {
        $title = 'Compte activé';
        $body = 'Votre compte a été activé. Vous pouvez vous connecter et accéder à l\'application.';

        $notification = AppNotification::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'type' => 'account.activated',
            'title' => $title,
            'body' => $body,
            'data' => [
                'user_id' => $user->id,
                'activated_at' => now()->toDateTimeString(),
            ],
        ]);

        try {
            $this->webPushService->sendToUser($user, $notification);
        } catch (\Throwable $e) {
            report($e);
        }

        return $notification;
    }

    private function sendToAdminsSafe(AppNotification $notification): void
    {
        try {
            $this->webPushService->sendToAdmins($notification);
        } catch (\Throwable $e) {
            // Ne pas faire échouer la création produit/vente si le push échoue
            report($e);
        }
    }
}
