<?php

namespace Src\Infrastructure\GlobalCommerce\Support;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class GcShopResolver
{
    /**
     * Résout l'UUID boutique (gc_products, gc_sales, etc.).
     * Ne jamais retourner tenant_id à la place du shop_id.
     */
    public static function resolveShopId(Request $request): string
    {
        $shopId = self::tryResolveShopId($request);
        if ($shopId === null) {
            abort(403, 'Shop ID not found.');
        }

        return $shopId;
    }

    /**
     * Comme resolveShopId mais sans HTTP 403 (assistant, voix, diagnostics).
     */
    public static function tryResolveShopId(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        if (! $depotId && $request->hasSession()) {
            $depotId = $request->session()->get('current_depot_id');
        }

        if ($depotId && $user->tenant_id && Schema::hasTable('shops')) {
            $shop = Shop::query()
                ->where('depot_id', $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            $shop = Shop::query()->find($user->shop_id);
            if ($shop && (int) $shop->tenant_id === (int) $user->tenant_id) {
                return (string) $shop->id;
            }
        }

        if ($user->tenant_id && Schema::hasTable('shops')) {
            $shop = Shop::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        return null;
    }

    public static function findShop(Request $request): ?Shop
    {
        $shopId = self::tryResolveShopId($request);

        return $shopId !== null ? Shop::query()->find($shopId) : null;
    }

    /**
     * Tenant métier (clients, devises) — distinct du shop_id catalogue.
     */
    public static function resolveTenantId(Request $request): ?int
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        if ($user->tenant_id) {
            return (int) $user->tenant_id;
        }

        $shop = self::findShop($request);

        return $shop ? (int) $shop->tenant_id : null;
    }
}
