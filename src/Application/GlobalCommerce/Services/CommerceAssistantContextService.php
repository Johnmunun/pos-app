<?php

namespace Src\Application\GlobalCommerce\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Src\Application\Common\Services\AssistantSalesProfitContextBuilder;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Support\GcShopResolver;
use App\Models\Customer;

/**
 * Prépare le contexte léger pour l'Assistant Intelligent Commerce (nommé 'code').
 * Aucune requête lourde : agrégats limités, pas de scan complet.
 */
class CommerceAssistantContextService
{
    private const CACHE_TTL_SECONDS = 90;

    public function getContext(Request $request, ?string $productSearch = null, ?array $permissions = null): array
    {
        $user = $request->user();
        if ($user === null) {
            return ['error' => 'non_authenticated'];
        }

        if ($permissions === null && method_exists($user, 'permissionCodes')) {
            $permissions = $user->permissionCodes();
        }
        $permissions = $permissions ?? [];

        $shopId = GcShopResolver::tryResolveShopId($request);
        if ($shopId === null) {
            return ['error' => 'no_shop', 'navigation' => $this->getNavigation($permissions, $user)];
        }

        $cacheKey = 'commerce_assistant_context:' . $shopId;
        $now = now()->format('Y-m-d');

        $currency = $this->getShopCurrency($request, $shopId);
        $context = [
            'date' => $now,
            'user_name' => $user->name ?? $user->email ?? 'Utilisateur',
            'navigation' => $this->getNavigation($permissions, $user),
            'customers_count' => $this->getCustomersCount($shopId),
            'sales_today' => $this->getSalesToday($shopId),
            'sales_total_all_time' => $this->getSalesTotalAllTime($shopId),
            'sales_last_30_days' => $this->getSalesLast30Days($shopId),
            'stock_alerts' => $this->getCachedOrCompute($cacheKey . ':alerts', fn () => $this->getStockAlerts($shopId)),
            'products_out_of_stock' => $this->getProductsOutOfStock($shopId),
            'products_low_stock' => $this->getProductsLowStock($shopId),
            'currency' => $currency,
            ...$this->getCachedOrCompute($cacheKey . ':profit', fn () => AssistantSalesProfitContextBuilder::forCommerce($shopId, $currency)),
        ];

        if ($productSearch !== null && $productSearch !== '') {
            $context['products_matching'] = $this->searchProductsLight($shopId, $productSearch, $currency);
        } else {
            $context['products_matching'] = [];
        }

        return $context;
    }

    private function getCachedOrCompute(string $key, callable $callback): array
    {
        return Cache::remember($key, self::CACHE_TTL_SECONDS, $callback);
    }

    private function getShopCurrency(Request $request, string $shopId): string
    {
        $shop = \App\Models\Shop::find($shopId);
        $tenantId = GcShopResolver::resolveTenantId($request) ?? ($shop !== null ? (int) $shop->tenant_id : null);
        if ($tenantId) {
            $defaultCurrency = \App\Models\Currency::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
            if ($defaultCurrency && ! empty($defaultCurrency->code)) {
                return $defaultCurrency->code;
            }
        }
        if ($shop && ! empty($shop->currency)) {
            return $shop->currency;
        }

        return 'CDF';
    }

    private function getSalesToday(string $shopId): array
    {
        $today = now()->format('Y-m-d');
        $row = SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->whereRaw('DATE(created_at) = ?', [$today])
            ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        if ($row === null) {
            return ['total_sales' => 0, 'total_revenue' => 0.0, 'date' => $today];
        }
        return [
            'total_sales' => (int) ($row->total_sales ?? 0),
            'total_revenue' => (float) ($row->total_revenue ?? 0),
            'date' => $today,
        ];
    }

    private function getSalesTotalAllTime(string $shopId): array
    {
        $row = SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        return [
            'total_sales' => (int) ($row->total_sales ?? 0),
            'total_revenue' => (float) ($row->total_revenue ?? 0),
            'period' => 'depuis le début',
        ];
    }

    private function getSalesLast30Days(string $shopId): array
    {
        $start = now()->subDays(30)->startOfDay()->format('Y-m-d H:i:s');
        $end = now()->endOfDay()->format('Y-m-d H:i:s');

        $rows = SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return $rows->map(fn (object $r): array => [
            'date' => (string) ($r->date ?? ''),
            'total_sales' => (int) ($r->total_sales ?? 0),
            'total_revenue' => (float) ($r->total_revenue ?? 0),
        ])->toArray();
    }

    private function getStockAlerts(string $shopId): array
    {
        $lowStock = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();
        $outOfStock = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        return [
            'low_stock_count' => $lowStock,
            'out_of_stock_count' => $outOfStock,
        ];
    }

    private function getCustomersCount(string $shopId): array
    {
        $tenantId = GcShopResolver::resolveTenantId(request());
        if (! $tenantId) {
            return ['total_active' => 0];
        }
        $total = Customer::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
        return ['total_active' => (int) $total];
    }

    private function getProductsOutOfStock(string $shopId): array
    {
        return ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->limit(25)
            ->get(['name', 'sku', 'stock', 'minimum_stock'])
            ->map(fn ($m) => [
                'name' => $m->name,
                'code' => $m->sku ?? '',
                'stock' => (float) $m->stock,
                'minimum_stock' => (float) ($m->minimum_stock ?? 0),
            ])
            ->toArray();
    }

    private function getProductsLowStock(string $shopId): array
    {
        return ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->limit(25)
            ->get(['name', 'sku', 'stock', 'minimum_stock'])
            ->map(fn ($m) => [
                'name' => $m->name,
                'code' => $m->sku ?? '',
                'stock' => (float) $m->stock,
                'minimum_stock' => (float) ($m->minimum_stock ?? 0),
            ])
            ->toArray();
    }

    private function searchProductsLight(string $shopId, string $query, string $currency = 'CDF'): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $like = '%' . $q . '%';
        $models = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)->orWhere('sku', 'like', $like);
            })
            ->limit(5)
            ->get(['id', 'name', 'sku', 'stock', 'sale_price_amount', 'sale_price_currency', 'purchase_price_amount', 'minimum_stock', 'unit']);

        if ($models->isEmpty()) {
            return [];
        }

        $shopIdInt = (int) $shopId;

        return $models->map(function ($m) use ($shopIdInt, $currency) {
            $sell = (float) $m->sale_price_amount;
            $cost = $m->purchase_price_amount !== null ? (float) $m->purchase_price_amount : null;
            $unitMargin = ($cost !== null && $sell > 0) ? round($sell - $cost, 2) : null;
            $marginPercent = ($unitMargin !== null && $sell > 0) ? round(($unitMargin / $sell) * 100, 1) : null;
            $stockQty = (float) $m->stock;

            return [
                'id' => $m->id,
                'name' => $m->name,
                'code' => $m->sku ?? '',
                'stock_quantity' => $stockQty,
                'selling_price' => $sell,
                'cost_price' => $cost,
                'unit_margin' => $unitMargin,
                'margin_percent' => $marginPercent,
                'profit_on_stock' => ($unitMargin !== null) ? round($unitMargin * $stockQty, 2) : null,
                'currency' => $m->sale_price_currency ?? $currency,
                'minimum_stock' => (float) ($m->minimum_stock ?? 0),
                'unit' => $m->unit ?? 'unité',
                'recent_stock_movements' => AssistantSalesProfitContextBuilder::commerceStockMovements($shopIdInt, (string) $m->id, 8),
            ];
        })->toArray();
    }

    private function getNavigation(array $permissions, $user): array
    {
        $isRoot = method_exists($user, 'isRoot') ? $user->isRoot() : (($user->type ?? null) === 'ROOT');
        $has = function ($perms) use ($permissions, $isRoot) {
            if ($isRoot || in_array('*', $permissions, true)) {
                return true;
            }
            foreach ((array) $perms as $p) {
                if (in_array($p, $permissions, true)) {
                    return true;
                }
            }
            return false;
        };

        $nav = [];
        $push = function (string $name, string $route, array $perms) use (&$nav, $has): void {
            if ($has($perms)) {
                $nav[] = ['name' => $name, 'route' => $route, 'label' => $name, 'path' => $route];
            }
        };
        $push('Dashboard Commerce', '/commerce/dashboard', ['module.commerce']);
        $push('Produits', '/commerce/products', ['module.commerce']);
        $push('Catégories', '/commerce/categories', ['module.commerce']);
        $push('Stock', '/commerce/stock', ['module.commerce']);
        $push('Ventes', '/commerce/sales', ['module.commerce']);
        $push('Achats', '/commerce/purchases', ['module.commerce']);
        $push('Fournisseurs', '/commerce/suppliers', ['module.commerce']);
        $push('Clients', '/commerce/customers', ['module.commerce']);
        $push('Rapports', '/commerce/reports', ['module.commerce']);

        return $nav;
    }
}
