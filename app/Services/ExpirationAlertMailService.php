<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\ExpirationAlertNotification;
use Illuminate\Support\Facades\Notification;
use Src\Infrastructure\Pharmacy\Models\ProductBatchModel;

class ExpirationAlertMailService
{
    private const WARNING_DAYS = 30;

    /**
     * Envoie les alertes expiration par email pour tous les shops ayant des lots en alerte.
     */
    public function sendExpirationAlerts(): int
    {
        $sent = 0;
        $shops = Shop::where('is_active', true)->get();

        foreach ($shops as $shop) {
            $summary = $this->buildSummaryForShop((string) $shop->id, $shop->name ?? 'Pharmacie');
            if ($summary['expired_count'] === 0 && $summary['expiring_soon_count'] === 0) {
                continue;
            }
            $users = $this->getNotifiableUsers((int) $shop->tenant_id);
            foreach ($users as $user) {
                if ($user->email) {
                    Notification::send($user, new ExpirationAlertNotification($summary));
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @return array{shop_name: string, expired_count: int, expiring_soon_count: int, warning_days: int, items: array<int, array{product_name: string, batch_number: string, quantity: int, expiration_date: string, status: string}>}
     */
    private function buildSummaryForShop(string $shopId, string $shopName): array
    {
        $now = new \DateTimeImmutable('today');
        $expired = ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->expired($now)
            ->with('product')
            ->orderBy('expiration_date')
            ->get();
        $expiring = ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->expiring(self::WARNING_DAYS, $now)
            ->with('product')
            ->orderBy('expiration_date')
            ->get();

        $items = [];
        foreach ($expired as $b) {
            $productName = $b->relationLoaded('product') && $b->product !== null ? $b->product->name : '';
            $expDate = $b->expiration_date !== null ? $b->expiration_date->format('d/m/Y') : '';
            $items[] = [
                'product_name' => $productName,
                'batch_number' => $b->batch_number ?? '',
                'quantity' => (int) $b->quantity,
                'expiration_date' => $expDate,
                'status' => 'Expiré',
            ];
        }
        foreach ($expiring as $b) {
            $productName = $b->relationLoaded('product') && $b->product !== null ? $b->product->name : '';
            $expDate = $b->expiration_date !== null ? $b->expiration_date->format('d/m/Y') : '';
            $items[] = [
                'product_name' => $productName,
                'batch_number' => $b->batch_number ?? '',
                'quantity' => (int) $b->quantity,
                'expiration_date' => $expDate,
                'status' => 'Expire bientôt',
            ];
        }

        return [
            'shop_name' => $shopName,
            'expired_count' => $expired->count(),
            'expiring_soon_count' => $expiring->count(),
            'warning_days' => self::WARNING_DAYS,
            'items' => $items,
        ];
    }

    /**
     * Utilisateurs à notifier : même tenant, avec permission expiration ou ROOT.
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function getNotifiableUsers(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        $users = User::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();

        return $users->filter(function (User $user) {
            if ($user->isRoot()) {
                return true;
            }
            return $user->hasPermission('pharmacy.expiration.view') || $user->hasPermission('pharmacy.batch.view');
        });
    }
}
