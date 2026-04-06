<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;

/**
 * Résout la boutique « canonique » pour le backoffice (dépôt, tenant, ROOT, legacy),
 * et les shop_id à inclure pour gc_* lorsque l’historique utilise tenant_id comme shop_id.
 */
final class TenantBackofficeShopResolver
{
    public function resolveShop(Request $request): Shop
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $userModel = User::find($user->id);
        $isRoot = $userModel?->isRoot() ?? false;
        $tenantId = $user->tenant_id !== null && $user->tenant_id !== '' ? (string) $user->tenant_id : null;

        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        if (!$depotId && $request->hasSession()) {
            $sid = $request->session()->get('current_depot_id');
            $depotId = $sid !== null && $sid !== '' ? (int) $sid : null;
        }
        if ($depotId && $tenantId !== null && Schema::hasTable('shops')) {
            $byDepot = Shop::query()
                ->where('depot_id', $depotId)
                ->where('tenant_id', $tenantId)
                ->first();
            if ($byDepot) {
                return $byDepot;
            }
        }

        $shop = null;

        if ($isRoot && $request->hasSession()) {
            $sessionShopId = $request->session()->get('current_storefront_shop_id');
            if ($sessionShopId && is_numeric($sessionShopId)) {
                $shop = Shop::find((int) $sessionShopId) ?: null;
            }
        }

        if (!$shop && $tenantId !== null) {
            $shop = Shop::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if (!$shop && !empty($user->shop_id)) {
            $candidate = Shop::find($user->shop_id);
            if ($candidate) {
                $cTenant = $candidate->tenant_id !== null ? (string) $candidate->tenant_id : null;
                if ($tenantId === null || $cTenant === $tenantId) {
                    $shop = $candidate;
                }
            }
        }

        if (!$shop && $tenantId !== null) {
            $legacy = Shop::find($tenantId);
            if ($legacy) {
                $lt = $legacy->tenant_id !== null ? (string) $legacy->tenant_id : null;
                if ($lt === null || $lt === $tenantId) {
                    $shop = $legacy;
                }
            }
        }

        if (!$shop && $isRoot) {
            abort(403, 'Veuillez sélectionner une boutique.');
        }
        if (!$shop) {
            abort(403, 'Boutique introuvable.');
        }

        return $shop;
    }

    /**
     * @return list<string>
     */
    public function globalCommerceInventoryShopIds(Shop $shop, ?string $tenantId): array
    {
        $ids = [(string) $shop->id];
        if ($tenantId === null || $tenantId === '' || !ctype_digit((string) $tenantId)) {
            return $ids;
        }
        if ((string) $tenantId === (string) $shop->id) {
            return $ids;
        }

        $hasLegacy = false;
        if (Schema::hasTable('gc_products') && GcProductModel::query()->where('shop_id', $tenantId)->exists()) {
            $hasLegacy = true;
        }
        if (!$hasLegacy && Schema::hasTable('gc_categories') && CategoryModel::query()->where('shop_id', $tenantId)->exists()) {
            $hasLegacy = true;
        }
        if ($hasLegacy) {
            $ids[] = (string) $tenantId;
        }

        return array_values(array_unique($ids));
    }
}
