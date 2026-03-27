<?php

namespace App\Services;

use App\Events\AdminNotificationBroadcast;
use App\Mail\EventNotificationMail;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Src\Infrastructure\Support\Models\SupportChatConversationModel;
use Src\Infrastructure\Support\Models\SupportChatMessageModel;

class AppNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly DynamicMailSettingsService $dynamicMailSettingsService
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
            'user_id' => (int) (Auth::id() ?? 0),
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
        if ($this->dynamicMailSettingsService->eventEnabled('sale_completed')) {
            $this->emailTenantUsersByPermissions(
                (int) ($tenantId ?? 0),
                $title,
                $body,
                ['sales.view', 'pharmacy.sales.view', 'commerce.sales.view', 'hardware.sales.view']
            );
        }
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
            'user_id' => (int) (Auth::id() ?? 0),
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
            $this->notificationService->sendToUser(
                (int) $user->id,
                (string) $notification->title,
                (string) $notification->body,
                (array) ($notification->data ?? [])
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $this->notifyActivationInSupportChat($user);
        if ($this->dynamicMailSettingsService->eventEnabled('account_activated')) {
            $this->sendEmailToUser($user, $title, $body);
        }

        return $notification;
    }

    public function notifyEcommerceOrder(string $title, string $body, ?int $tenantId = null): void
    {
        $notification = AppNotification::create([
            'user_id' => (int) (Auth::id() ?? 0),
            'tenant_id' => $tenantId,
            'type' => 'ecommerce.order.event',
            'title' => $title,
            'body' => $body,
            'data' => [
                'tenant_id' => $tenantId,
            ],
        ]);

        $this->sendToAdminsSafe($notification);
        if ($this->dynamicMailSettingsService->eventEnabled('ecommerce_order')) {
            $this->emailTenantUsersByPermissions(
                (int) ($tenantId ?? 0),
                $title,
                $body,
                ['ecommerce.order.view', 'ecommerce.order.manage', 'ecommerce.view', 'module.ecommerce']
            );
        }
        AdminNotificationBroadcast::dispatch($notification);
    }

    /**
     * Alerte + e-mail (une fois par mois et par tenant) lorsque le plafond ventes mensuelles est atteint.
     */
    public function notifySalesMonthlyLimitReached(int $tenantId, int $limit, int $currentCount): void
    {
        if ($tenantId <= 0) {
            return;
        }

        $cacheKey = 'sales_monthly_limit_notified:' . $tenantId . ':' . now()->format('Y-m');
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->endOfMonth());

        $title = 'Limite de ventes mensuelle atteinte';
        $body = sprintf(
            'Votre organisation a atteint le plafond de %d ventes pour le mois en cours (compteur : %d). '
            . 'Passez à un plan supérieur ou attendez le mois prochain pour enregistrer de nouvelles ventes.',
            $limit,
            $currentCount
        );

        $notification = AppNotification::create([
            'user_id' => (int) (Auth::id() ?? 0),
            'tenant_id' => $tenantId,
            'type' => 'billing.sales_monthly_limit',
            'title' => $title,
            'body' => $body,
            'data' => [
                'tenant_id' => $tenantId,
                'limit' => $limit,
                'current_count' => $currentCount,
            ],
        ]);

        $this->sendToAdminsSafe($notification);
        if ($this->dynamicMailSettingsService->eventEnabled('sales_monthly_limit')) {
            $this->emailTenantUsersByPermissions(
                $tenantId,
                $title,
                $body,
                [
                    'sales.view',
                    'pharmacy.sales.view',
                    'commerce.sales.view',
                    'hardware.sales.view',
                    'ecommerce.order.view',
                    'ecommerce.order.manage',
                    'ecommerce.view',
                    'module.ecommerce',
                ]
            );
        }
        AdminNotificationBroadcast::dispatch($notification);
    }

    private function notifyActivationInSupportChat(User $user): void
    {
        try {
            $conversation = SupportChatConversationModel::query()
                ->where('user_id', (int) $user->id)
                ->orderByDesc('id')
                ->first();

            if (!$conversation) {
                $conversation = SupportChatConversationModel::create([
                    'tenant_id' => $user->tenant_id ?? null,
                    'user_id' => (int) $user->id,
                    'assigned_to_user_id' => null,
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);
            }

            SupportChatMessageModel::create([
                'conversation_id' => (int) $conversation->id,
                'sender_user_id' => null,
                'sender_type' => 'support',
                'message' => 'Votre compte est maintenant actif. Bienvenue sur OmniPOS.',
            ]);

            $conversation->last_message_at = now();
            $conversation->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sendToAdminsSafe(AppNotification $notification): void
    {
        try {
            // ROOT/admins are typically tenant-scoped in data; broadcast + navbar still handle in-app.
            // For push, send to tenant if available, otherwise skip.
            if (!empty($notification->tenant_id)) {
                $this->notificationService->sendToTenant(
                    (int) $notification->tenant_id,
                    (string) $notification->title,
                    (string) $notification->body,
                    (array) ($notification->data ?? [])
                );
            }
        } catch (\Throwable $e) {
            // Ne pas faire échouer la création produit/vente si le push échoue
            report($e);
        }
    }

    private function sendEmailToUser(User $user, string $title, string $body): void
    {
        if (empty($user->email)) {
            return;
        }
        try {
            if (!$this->dynamicMailSettingsService->applyFromStorage()) {
                return;
            }
            Mail::to($user->email)->send(new EventNotificationMail($title, $body));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param array<int, string> $permissionCodes
     */
    private function emailTenantUsersByPermissions(int $tenantId, string $title, string $body, array $permissionCodes): void
    {
        if ($tenantId <= 0) {
            return;
        }

        try {
            if (!$this->dynamicMailSettingsService->applyFromStorage()) {
                return;
            }

            $users = User::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->get()
                ->filter(function (User $u) use ($permissionCodes) {
                    if (empty($u->email)) {
                        return false;
                    }
                    if ($u->isRoot()) {
                        return true;
                    }
                    foreach ($permissionCodes as $code) {
                        if ($u->hasPermission($code)) {
                            return true;
                        }
                    }
                    return false;
                });

            foreach ($users as $u) {
                Mail::to($u->email)->send(new EventNotificationMail($title, $body));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
