<?php

namespace Src\Application\Quincaillerie\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;

/**
 * Prépare un contexte léger pour l'assistant Quincaillerie (Hardware).
 * Règle clé : aucune requête lourde, uniquement quelques agrégats ciblés.
 */
class HardwareAssistantContextService
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

        $shopId = $this->resolveShopId($request);
        if (!$shopId) {
            return [
                'error' => 'no_shop',
                'navigation' => $this->getNavigation($permissions, $user),
            ];
        }

        $cacheKey = 'hardware_assistant_context:' . $shopId;
        $now = now()->format('Y-m-d');
        $currency = $this->getShopCurrency($shopId);

        $context = [
            'date' => $now,
            'user_name' => $user->name ?? $user->email ?? 'Utilisateur',
            'navigation' => $this->getNavigation($permissions, $user),
            'currency' => $currency,
            'sales_today' => $this->getSalesToday($shopId),
        ];

        // Comptages et résumés légers
        $context['products_summary'] = $this->getCachedOrCompute($cacheKey . ':products_summary', fn () => $this->getProductsSummary($shopId));
        $context['stock_alerts'] = $this->getCachedOrCompute($cacheKey . ':stock_alerts', fn () => $this->getStockAlerts($shopId));
        $context['products_out_of_stock'] = $this->getProductsOutOfStock($shopId);
        $context['products_low_stock'] = $this->getProductsLowStock($shopId);

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

    private function resolveShopId(Request $request): ?string
    {
        $user = $request->user();
        $depotId = $request->session()->get('current_depot_id');

        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }

        return $user->tenant_id ? (string) $user->tenant_id : null;
    }

    private function getShopCurrency(string $shopId): string
    {
        $shop = \App\Models\Shop::find($shopId);
        $tenantId = $shop !== null ? ($shop->tenant_id ?? $shopId) : $shopId;

        $defaultCurrency = \App\Models\Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($defaultCurrency && !empty($defaultCurrency->code)) {
            return $defaultCurrency->code;
        }

        if ($shop && !empty($shop->currency)) {
            return $shop->currency;
        }

        return 'CDF';
    }

    /**
     * Résumé produits (léger) : nombre total et nombre actifs.
     */
    private function getProductsSummary(string $shopId): array
    {
        $total = ProductModel::where('shop_id', $shopId)->count();
        $active = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->count();

        return [
            'products_total' => (int) $total,
            'products_active' => (int) $active,
        ];
    }

    /**
     * Alertes stock : comptages uniquement (quincaillerie).
     */
    private function getStockAlerts(string $shopId): array
    {
        $lowStock = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->where('stock', '>', 0)
            ->count();

        $outOfStock = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        return [
            'low_stock_count' => (int) $lowStock,
            'out_of_stock_count' => (int) $outOfStock,
        ];
    }

    /**
     * Liste des produits en rupture (max 25).
     */
    private function getProductsOutOfStock(string $shopId): array
    {
        return ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->limit(25)
            ->get(['name', 'code', 'stock', 'minimum_stock'])
            ->map(fn (ProductModel $m) => [
                'name' => $m->name,
                'code' => $m->code ?? '',
                'stock' => (float) ($m->stock ?? 0),
                'minimum_stock' => (float) ($m->minimum_stock ?? 0),
            ])
            ->toArray();
    }

    /**
     * Liste des produits en stock bas (max 25).
     */
    private function getProductsLowStock(string $shopId): array
    {
        return ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->limit(25)
            ->get(['name', 'code', 'stock', 'minimum_stock'])
            ->map(fn (ProductModel $m) => [
                'name' => $m->name,
                'code' => $m->code ?? '',
                'stock' => (float) ($m->stock ?? 0),
                'minimum_stock' => (float) ($m->minimum_stock ?? 0),
            ])
            ->toArray();
    }

    private function getSalesToday(string $shopId): array
    {
        $today = now()->format('Y-m-d');

        $row = SaleModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'COMPLETED')
            ->whereRaw('DATE(COALESCE(completed_at, created_at)) = ?', [$today])
            ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        if ($row === null) {
            return ['total_sales' => 0, 'total_revenue' => 0.0, 'date' => $today];
        }

        $data = (object) $row->toArray();

        return [
            'total_sales' => (int) ($data->total_sales ?? 0),
            'total_revenue' => (float) ($data->total_revenue ?? 0),
            'date' => $today,
        ];
    }

    /**
     * Recherche produit légère pour l'assistant (max 5 résultats).
     */
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
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('barcode', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'code', 'barcode', 'stock', 'price_amount', 'price_currency', 'minimum_stock', 'type_unite', 'quantite_par_unite']);

        if ($models->isEmpty()) {
            return [];
        }

        return $models->map(fn (ProductModel $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'code' => $m->code ?? '',
            'barcode' => $m->barcode ?? '',
            'stock_quantity' => (float) ($m->stock ?? 0),
            'selling_price' => (float) ($m->price_amount ?? 0),
            'currency' => $m->price_currency ?? $currency,
            'minimum_stock' => (float) ($m->minimum_stock ?? 0),
            'unit' => $m->type_unite ?? 'UNITE',
            'quantity_per_unit' => (int) ($m->quantite_par_unite ?? 1),
        ])->toArray();
    }

    /**
     * Navigation Hardware selon les permissions.
     */
    private function getNavigation(array $permissions, $user): array
    {
        $isRoot = method_exists($user, 'isRoot') ? $user->isRoot() : (($user->type ?? null) === 'ROOT');

        $has = function (array $perms) use ($permissions, $isRoot): bool {
            if ($isRoot || in_array('*', $permissions, true)) {
                return true;
            }
            foreach ($perms as $p) {
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

        $push('Dashboard Quincaillerie', '/hardware/dashboard', ['module.hardware']);
        $push('Produits Quincaillerie', '/hardware/products', ['hardware.product.view', 'hardware.product.manage']);
        $push('Catégories Quincaillerie', '/hardware/categories', ['hardware.category.view', 'hardware.category.create', 'hardware.category.update']);
        $push('Stock Quincaillerie', '/hardware/stock', ['hardware.stock.view', 'hardware.stock.manage']);
        $push('Mouvements de stock Quincaillerie', '/hardware/stock/movements', ['hardware.stock.movement.view', 'hardware.stock.manage']);
        $push('Fournisseurs Quincaillerie', '/hardware/suppliers', ['hardware.supplier.view']);
        $push('Clients Quincaillerie', '/hardware/customers', ['hardware.customer.view']);
        $push('Ventes Quincaillerie', '/hardware/sales', ['hardware.sales.view', 'hardware.sales.manage']);
        $push('Achats Quincaillerie', '/hardware/purchases', ['hardware.purchases.view', 'hardware.purchases.manage']);
        $push('Rapports Quincaillerie', '/hardware/reports', ['hardware.report.view', 'hardware.sales.view']);

        return $nav;
    }
}

