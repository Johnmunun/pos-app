<?php

namespace Src\Application\Pharmacy\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\ProductBatchModel;
use Src\Infrastructure\Pharmacy\Models\CustomerModel;

/**
 * Prépare le contexte léger pour l'Assistant Intelligent.
 * Aucune requête lourde : agrégats limités, pas de scan complet.
 */
class PharmacyAssistantContextService
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
            return ['error' => 'no_shop', 'navigation' => $this->getNavigation($permissions, $user)];
        }

        $cacheKey = 'pharmacy_assistant_context:' . $shopId;
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
            'dashboard_summary' => $this->getCachedOrCompute($cacheKey . ':dashboard', fn () => $this->getDashboardSummary($request, $shopId)),
            'expiring_soon_products' => $this->getCachedOrCompute($cacheKey . ':expiring', fn () => $this->getExpiringSoonProducts($shopId)),
            'currency' => $currency,
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

    private function resolveShopId(Request $request): ?string
    {
        $user = $request->user();
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }
        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }
        return $user->tenant_id ? (string) $user->tenant_id : null;
    }

    /** Ventes du jour : 1 requête agrégée */
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
        $r = (object) $row->toArray();
        return [
            'total_sales' => (int) ($r->total_sales ?? 0),
            'total_revenue' => (float) ($r->total_revenue ?? 0),
            'date' => $today,
        ];
    }

    /** Revenus totaux depuis le début de la boutique : 1 requête agrégée */
    private function getSalesTotalAllTime(string $shopId): array
    {
        $row = SaleModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'COMPLETED')
            ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        return [
            'total_sales' => (int) ($row->total_sales ?? 0),
            'total_revenue' => (float) ($row->total_revenue ?? 0),
            'period' => 'depuis le début',
        ];
    }

    /** Ventes par jour sur les 30 derniers jours (pour répondre "pour le 20 février") */
    private function getSalesLast30Days(string $shopId): array
    {
        $start = now()->subDays(30)->startOfDay()->format('Y-m-d H:i:s');
        $end = now()->endOfDay()->format('Y-m-d H:i:s');

        $rows = SaleModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'COMPLETED')
            ->whereBetween(\Illuminate\Support\Facades\DB::raw('COALESCE(completed_at, created_at)'), [$start, $end])
            ->selectRaw('DATE(COALESCE(completed_at, created_at)) as date, COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(completed_at, created_at))'))
            ->orderBy('date')
            ->get();

        /** @var array<int, array{date: string, total_sales: int, total_revenue: float}> */
        return $rows->map(fn (object $r): array => [
            'date' => (string) ($r->date ?? ''),
            'total_sales' => (int) ($r->total_sales ?? 0),
            'total_revenue' => (float) ($r->total_revenue ?? 0),
        ])->toArray();
    }

    /** Même logique que SaleController::getEffectiveShopCurrency : devise par défaut du tenant puis shop. */
    private function getShopCurrency(Request $request, string $shopId): string
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

    /** Alertes stock : comptages uniquement (déjà utilisés par le dashboard) */
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

    /** Nombre total de clients actifs pour la boutique (léger) */
    private function getCustomersCount(string $shopId): array
    {
        $total = CustomerModel::query()
            ->byShop((int) $shopId)
            ->active()
            ->count();

        return ['total_active' => (int) $total];
    }

    /** Liste des produits en rupture (stock <= 0), max 25 pour garder le contexte léger */
    private function getProductsOutOfStock(string $shopId): array
    {
        return ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->limit(25)
            ->get(['name', 'code', 'stock', 'minimum_stock'])
            ->map(fn ($m) => [
                'name' => $m->name,
                'code' => $m->code ?? '',
                'stock' => (int) $m->stock,
                'minimum_stock' => (int) ($m->minimum_stock ?? 0),
            ])
            ->toArray();
    }

    /** Liste des produits en stock bas (stock <= minimum_stock), max 25 */
    private function getProductsLowStock(string $shopId): array
    {
        return ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->limit(25)
            ->get(['name', 'code', 'stock', 'minimum_stock'])
            ->map(fn ($m) => [
                'name' => $m->name,
                'code' => $m->code ?? '',
                'stock' => (int) $m->stock,
                'minimum_stock' => (int) ($m->minimum_stock ?? 0),
            ])
            ->toArray();
    }

    /** Produits dont un lot expire dans les 30 prochains jours (max 15 produits, tri par date croissante) */
    private function getExpiringSoonProducts(string $shopId): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('pharmacy_product_batches')) {
            return [];
        }
        $now = new \DateTimeImmutable();
        $threshold = $now->modify('+30 days');
        $batches = ProductBatchModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->where('expiration_date', '>=', $now->format('Y-m-d'))
            ->where('expiration_date', '<=', $threshold->format('Y-m-d'))
            ->orderBy('expiration_date')
            ->with('product:id,name,code')
            ->get();
        $today = \Carbon\Carbon::now()->startOfDay();
        $byProduct = [];
        foreach ($batches as $b) {
            $product = $b->product;
            $productId = $product ? $product->id : $b->product_id;
            $expDate = $b->expiration_date;
            if (!$expDate || $expDate->isPast()) {
                continue;
            }
            $daysRemaining = (int) $today->diffInDays($expDate->copy()->startOfDay(), false);
            if ($daysRemaining < 0) {
                continue;
            }
            if (!isset($byProduct[$productId]) || $expDate->format('Y-m-d') < ($byProduct[$productId]['expiration_date'] ?? '9999-99-99')) {
                $byProduct[$productId] = [
                    'name' => $product ? $product->name : '—',
                    'code' => $product && $product->code !== null ? $product->code : '',
                    'expiration_date' => $expDate->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                ];
            }
        }
        usort($byProduct, fn ($a, $b) => strcmp($a['expiration_date'], $b['expiration_date']));
        return array_slice($byProduct, 0, 15);
    }

    /** Résumé type dashboard (léger) */
    private function getDashboardSummary(Request $request, string $shopId): array
    {
        try {
            $dashboard = app(DashboardService::class);
            $stats = $dashboard->getDashboardStats($shopId);
            return [
                'products_total' => $stats['products']['total'] ?? 0,
                'products_active' => $stats['products']['active'] ?? 0,
                'inventory_total_value' => $stats['inventory']['total_value'] ?? 0,
                'low_stock_count' => $stats['inventory']['low_stock_count'] ?? 0,
                'expiring_soon_count' => $stats['expiry']['expiring_soon_count'] ?? 0,
                'alerts' => $stats['alerts'] ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'products_total' => 0,
                'products_active' => 0,
                'inventory_total_value' => 0,
                'low_stock_count' => 0,
                'expiring_soon_count' => 0,
                'alerts' => [],
            ];
        }
    }

    /** Recherche produit légère : max 5 résultats par nom/code, avec date expiration la plus proche si disponible */
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
                $builder->where('name', 'like', $like)->orWhere('code', 'like', $like);
            })
            ->limit(5)
            ->get(['id', 'name', 'code', 'stock', 'price_amount', 'price_currency', 'minimum_stock', 'unit']);
        if ($models->isEmpty()) {
            return [];
        }
        $ids = $models->pluck('id')->toArray();
        $nearestExpiry = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_product_batches')) {
            $rows = ProductBatchModel::query()
                ->where('shop_id', $shopId)
                ->whereIn('product_id', $ids)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->where('expiration_date', '>=', now()->format('Y-m-d'))
                ->selectRaw('product_id, MIN(expiration_date) as expiration_date')
                ->groupBy('product_id')
                ->get();
            foreach ($rows as $r) {
                $nearestExpiry[$r->product_id] = $r->expiration_date instanceof \Carbon\Carbon
                    ? $r->expiration_date->format('Y-m-d')
                    : (string) $r->expiration_date;
            }
        }
        return $models->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'code' => $m->code ?? '',
            'stock_quantity' => (int) $m->stock,
            'selling_price' => (float) $m->price_amount,
            'currency' => $m->price_currency ?? $currency,
            'minimum_stock' => (int) ($m->minimum_stock ?? 0),
            'unit' => $m->unit ?? 'unité',
            'expiration_date' => $nearestExpiry[$m->id] ?? null,
        ])->toArray();
    }

    /** Navigation selon permissions (liste des modules accessibles) */
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
        $push('Dashboard Pharmacie', '/pharmacy/dashboard', ['module.pharmacy', 'pharmacy.sales.view']);
        $push('Produits', '/pharmacy/products', ['pharmacy.pharmacy.product.manage', 'pharmacy.product.manage']);
        $push('Catégories', '/pharmacy/categories', ['pharmacy.pharmacy.product.manage', 'pharmacy.product.manage']);
        $push('Stock', '/pharmacy/stock', ['pharmacy.pharmacy.stock.manage', 'stock.view']);
        $push('Mouvements de stock', '/pharmacy/stock/movements', ['pharmacy.pharmacy.stock.manage', 'stock.view']);
        $push('Inventaires', '/pharmacy/inventories', ['inventory.view']);
        $push('Expirations', '/pharmacy/expirations', ['pharmacy.expiration.view', 'pharmacy.batch.view']);
        $push('Ventes', '/pharmacy/sales', ['pharmacy.sales.view', 'pharmacy.sales.manage']);
        $push('Achats', '/pharmacy/purchases', ['pharmacy.purchases.view', 'pharmacy.purchases.manage']);
        $push('Fournisseurs', '/pharmacy/suppliers', ['pharmacy.supplier.view']);
        $push('Clients', '/pharmacy/customers', ['pharmacy.customer.view']);
        $push('Rapports', '/pharmacy/reports', ['pharmacy.report.view']);
        $push('Dashboard Finance', '/finance/dashboard', ['finance.dashboard.view', 'finance.report.view']);
        $push('Dépenses', '/finance/expenses', ['finance.dashboard.view', 'finance.report.view']);
        $push('Paramètres de la boutique', '/settings', ['settings.view']);
        $push('Gestion des devises', '/settings/currencies', ['settings.currency.view']);
        if ($isRoot) {
            $nav[] = ['name' => 'Gestion des utilisateurs', 'route' => '/admin/users', 'label' => 'Gestion des utilisateurs', 'path' => '/admin/users'];
        }

        return $nav;
    }
}
