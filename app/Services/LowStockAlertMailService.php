<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Support\Facades\Notification;
use Src\Infrastructure\Pharmacy\Models\ProductModel;

class LowStockAlertMailService
{
    /**
     * Envoie les alertes stock faible par email pour tous les shops concernés.
     */
    public function sendLowStockAlerts(): int
    {
        $sent = 0;
        $shops = Shop::where('is_active', true)->get();

        foreach ($shops as $shop) {
            $items = ProductModel::query()
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->whereNotNull('minimum_stock')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->get()
                ->map(fn ($p) => [
                    'product_name' => $p->name ?? '',
                    'code' => $p->code ?? '',
                    'current_stock' => (int) $p->stock,
                    'minimum_stock' => (int) $p->minimum_stock,
                ])
                ->values()
                ->all();

            if (empty($items)) {
                continue;
            }

            $summary = [
                'shop_name' => $shop->name ?? 'Pharmacie',
                'items' => $items,
            ];
            $users = $this->getNotifiableUsers((int) $shop->tenant_id);
            foreach ($users as $user) {
                if ($user->email) {
                    Notification::send($user, new LowStockAlertNotification($summary));
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
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
            return $user->hasPermission('pharmacy.pharmacy.stock.manage')
                || $user->hasPermission('stock.view')
                || $user->hasPermission('pharmacy.product.view');
        });
    }
}
