<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel as PharmacyProductModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as QuincaillerieProductModel;

class LowStockAlertMailService
{
    public function __construct(
        private readonly DynamicMailSettingsService $dynamicMailSettingsService
    ) {
    }

    /**
     * Envoie les alertes stock faible par email pour tous les shops concernés
     * (produits pharmacie, quincaillerie, global commerce selon les tables présentes).
     */
    public function sendLowStockAlerts(): int
    {
        $this->dynamicMailSettingsService->applyFromStorage();
        if (!$this->dynamicMailSettingsService->eventEnabled('stock_low')) {
            return 0;
        }

        $sent = 0;
        $shops = Shop::where('is_active', true)->get();

        foreach ($shops as $shop) {
            $items = $this->collectLowStockItemsForShop($shop);

            if (empty($items)) {
                continue;
            }

            $summary = [
                'shop_name' => $shop->name ?? 'Boutique',
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
     * @return array<int, array{product_name: string, code: string, current_stock: int, minimum_stock: int}>
     */
    private function collectLowStockItemsForShop(Shop $shop): array
    {
        $shopId = $shop->id;
        $items = [];

        if (Schema::hasTable('pharmacy_products')) {
            $rows = PharmacyProductModel::query()
                ->where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('minimum_stock')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->get();

            foreach ($rows as $p) {
                $items[] = [
                    'product_name' => '[Pharmacie] '.($p->name ?? ''),
                    'code' => (string) ($p->code ?? ''),
                    'current_stock' => (int) $p->stock,
                    'minimum_stock' => (int) $p->minimum_stock,
                ];
            }
        }

        if (Schema::hasTable('quincaillerie_products')) {
            $rows = QuincaillerieProductModel::query()
                ->where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('minimum_stock')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->get();

            foreach ($rows as $p) {
                $items[] = [
                    'product_name' => '[Quincaillerie] '.($p->name ?? ''),
                    'code' => (string) ($p->code ?? ''),
                    'current_stock' => (int) round((float) $p->stock),
                    'minimum_stock' => (int) round((float) $p->minimum_stock),
                ];
            }
        }

        if (Schema::hasTable('gc_products')) {
            $rows = GcProductModel::query()
                ->where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('minimum_stock')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->get();

            foreach ($rows as $p) {
                $items[] = [
                    'product_name' => '[Commerce] '.($p->name ?? ''),
                    'code' => (string) ($p->sku ?? $p->barcode ?? ''),
                    'current_stock' => (int) round((float) $p->stock),
                    'minimum_stock' => (int) round((float) $p->minimum_stock),
                ];
            }
        }

        usort($items, fn (array $a, array $b): int => ($a['current_stock'] <=> $b['current_stock']));

        return $items;
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

            $permissionCodes = [
                'pharmacy.pharmacy.stock.manage',
                'stock.view',
                'pharmacy.product.view',
                'hardware.stock.view',
                'hardware.stock.manage',
                'commerce.stock.view',
                'commerce.stock.manage',
                'ecommerce.stock.view',
                'ecommerce.stock.manage',
                'kiosk.stock.view',
            ];

            foreach ($permissionCodes as $code) {
                if ($user->hasPermission($code)) {
                    return true;
                }
            }

            return false;
        });
    }
}
