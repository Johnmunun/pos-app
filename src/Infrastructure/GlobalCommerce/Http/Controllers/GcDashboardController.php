<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;
use Src\Infrastructure\GlobalCommerce\Support\GcShopResolver;
use Carbon\Carbon;

class GcDashboardController
{
    private function getShopId(Request $request): string
    {
        return GcShopResolver::resolveShopId($request);
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $last7 = $now->copy()->subDays(7)->startOfDay();

        $user = $request->user();
        $tenantId = $user?->tenant_id;

        // Stats produits
        $productModel = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::class;
        $productsTotal = (int) $productModel::where('shop_id', $shopId)->count();
        $productsActive = (int) $productModel::where('shop_id', $shopId)->where('is_active', true)->count();

        // Stats stock (agrégats SQL — évite de charger tous les produits actifs)
        $stockRow = $productModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->selectRaw('
                COALESCE(SUM(COALESCE(purchase_price_amount, 0) * COALESCE(stock, 0)), 0) as total_value,
                COALESCE(SUM(CASE WHEN COALESCE(stock, 0) <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock,
                COALESCE(SUM(CASE WHEN COALESCE(stock, 0) > 0 AND COALESCE(stock, 0) <= COALESCE(minimum_stock, 0) THEN 1 ELSE 0 END), 0) as low_stock,
                COALESCE(SUM(CASE WHEN COALESCE(stock, 0) > COALESCE(minimum_stock, 0) AND COALESCE(stock, 0) <= COALESCE(minimum_stock, 0) * 2 THEN 1 ELSE 0 END), 0) as medium_stock,
                COALESCE(SUM(CASE WHEN COALESCE(stock, 0) > COALESCE(minimum_stock, 0) * 2 THEN 1 ELSE 0 END), 0) as good_stock
            ')
            ->first();

        $totalValue = (float) ($stockRow->total_value ?? 0);
        $outOfStockCount = (int) ($stockRow->out_of_stock ?? 0);
        $lowStockCount = (int) ($stockRow->low_stock ?? 0);
        $stockStatus = [
            'out_of_stock' => $outOfStockCount,
            'low_stock' => $lowStockCount,
            'medium_stock' => (int) ($stockRow->medium_stock ?? 0),
            'good_stock' => (int) ($stockRow->good_stock ?? 0),
        ];

        // Stats catégories
        $categoryModel = \Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel::class;
        $categoriesTotal = (int) $categoryModel::where('shop_id', $shopId)->count();
        $categoriesActive = (int) $categoryModel::where('shop_id', $shopId)->where('is_active', true)->count();

        // Stats fournisseurs
        $supplierModel = \Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel::class;
        $suppliersTotal = 0;
        $suppliersActive = 0;
        if ($tenantId) {
            try {
                $suppliersTotal = (int) $supplierModel::where('tenant_id', $tenantId)->count();
                $suppliersActive = (int) $supplierModel::where('tenant_id', $tenantId)->where('is_active', true)->count();
            } catch (\Throwable $e) {
                // Ignore if table doesn't exist
            }
        }

        // Stats clients
        $customersTotal = 0;
        $customersActive = 0;
        if ($tenantId && \Illuminate\Support\Facades\Schema::hasTable('gc_customers')) {
            try {
                $customersTotal = (int) \Illuminate\Support\Facades\DB::table('gc_customers')
                    ->where('tenant_id', $tenantId)
                    ->count();
                $customersActive = (int) \Illuminate\Support\Facades\DB::table('gc_customers')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->count();
            } catch (\Throwable $e) {
                // Ignore if error
            }
        }

        // Stats ventes (statut completed, insensible à la casse pour compat COMPLETED/completed)
        $salesTodayTotal = (float) SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');
        $salesTodayCount = (int) SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->where('created_at', '>=', $todayStart)
            ->count();
        $salesLast7Total = (float) SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->where('created_at', '>=', $last7)
            ->sum('total_amount');
        $salesLast7Count = (int) SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->where('created_at', '>=', $last7)
            ->count();

        $yesterdayStart = $now->copy()->subDay()->startOfDay();
        $yesterdayEnd = $now->copy()->subDay()->endOfDay();
        $salesYesterdayTotal = (float) SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->sum('total_amount');
        $salesGrowthPercent = null;
        if ($salesYesterdayTotal > 0.0001) {
            $salesGrowthPercent = round((($salesTodayTotal - $salesYesterdayTotal) / $salesYesterdayTotal) * 100, 1);
        } elseif ($salesTodayTotal > 0) {
            $salesGrowthPercent = 100.0;
        }

        $averageBasketToday = $salesTodayCount > 0
            ? round($salesTodayTotal / $salesTodayCount, 2)
            : 0.0;

        $purchasesTodayTotal = 0.0;
        $purchasesTodayCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_purchases')) {
            $purchasesTodayTotal = (float) PurchaseModel::query()
                ->where('shop_id', $shopId)
                ->where('created_at', '>=', $todayStart)
                ->sum('total_amount');
            $purchasesTodayCount = (int) PurchaseModel::query()
                ->where('shop_id', $shopId)
                ->where('created_at', '>=', $todayStart)
                ->count();
        }

        $recentActivities = $this->buildRecentActivities($shopId, 8);
        $period = (int) $request->input('period', 14);
        if (!in_array($period, [7, 14, 30], true)) {
            $period = 14;
        }
        $dateFrom = $request->input('from');
        $dateTo = $request->input('to');
        $useDateRange = $dateFrom && $dateTo;

        // Graphique ventes
        $chartSalesLastDays = $useDateRange
            ? $this->getSalesChartDataByRange($shopId, $dateFrom, $dateTo)
            : $this->getSalesChartData($shopId, $period);

        // Graphique répartition stock
        $chartStockDistribution = $this->getStockDistributionChartData($stockStatus);

        // Alerts
        $alerts = [];
        if ($outOfStockCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$outOfStockCount} produit(s) en rupture de stock",
                'priority' => 'Haute',
            ];
        }
        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$lowStockCount} produit(s) avec stock bas",
                'priority' => 'Moyenne',
            ];
        }

        $currency = $request->session()->get('shop')['currency'] ?? 'CDF';
        if (!$currency) {
            $currency = 'CDF';
        }

        // Stockage médias: images produits GlobalCommerce uniquement
        $productImageCount = (int) GcProductModel::where('shop_id', $shopId)
            ->whereNotNull('image_path')
            ->count();

        return Inertia::render('Commerce/Dashboard', [
            'stats' => [
                'products' => [
                    'total' => $productsTotal,
                    'active' => $productsActive,
                    'inactive' => $productsTotal - $productsActive,
                ],
                'inventory' => [
                    'total_value' => round($totalValue, 2),
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                    'stock_status' => $stockStatus,
                ],
                'categories' => [
                    'total' => $categoriesTotal,
                    'active' => $categoriesActive,
                ],
                'suppliers' => [
                    'total' => $suppliersTotal,
                    'active' => $suppliersActive,
                ],
                'customers' => [
                    'total' => $customersTotal,
                    'active' => $customersActive,
                ],
                'sales' => [
                    'today_total' => round($salesTodayTotal, 2),
                    'today_count' => $salesTodayCount,
                    'yesterday_total' => round($salesYesterdayTotal, 2),
                    'growth_percent' => $salesGrowthPercent,
                    'average_basket_today' => $averageBasketToday,
                    'last_7_days_total' => round($salesLast7Total, 2),
                    'last_7_days_count' => $salesLast7Count,
                ],
                'purchases' => [
                    'today_total' => round($purchasesTodayTotal, 2),
                    'today_count' => $purchasesTodayCount,
                ],
                'recent_activities' => $recentActivities,
                'alerts' => $alerts,
                'media_storage' => [
                    'images_count' => $productImageCount,
                    'used_mb' => null,
                    'limit_mb' => 100.0,
                ],
            ],
            'chartSalesLastDays' => $chartSalesLastDays,
            'chartStockDistribution' => $chartStockDistribution,
            'filters' => [
                'period' => $period,
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * Ventes par jour sur les N derniers jours (pour graphique).
     * @return array<int, array{date: string, total: float, count: int}>
     */
    private function getSalesChartData(string $shopId, int $days): array
    {
        $start = now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s');
        $end = now()->endOfDay()->format('Y-m-d H:i:s');

        $rows = SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $date = $r->getAttribute('date');
            $total = (float) $r->getAttribute('total');
            $count = (int) $r->getAttribute('count');
            $byDate[$date] = ['date' => $date, 'total' => $total, 'count' => $count];
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $result[] = $byDate[$d] ?? ['date' => $d, 'total' => 0.0, 'count' => 0];
        }
        return $result;
    }

    /**
     * Ventes par jour sur une plage de dates (date début, date fin).
     *
     * @return array<int, array{date: string, total: float, count: int}>
     */
    private function getSalesChartDataByRange(string $shopId, string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        $end = Carbon::parse($dateTo)->endOfDay()->format('Y-m-d H:i:s');

        $rows = SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $date = $r->getAttribute('date');
            $total = (float) $r->getAttribute('total');
            $count = (int) $r->getAttribute('count');
            $byDate[$date] = ['date' => $date, 'total' => $total, 'count' => $count];
        }

        $result = [];
        $current = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        while ($current->lte($endDate)) {
            $d = $current->format('Y-m-d');
            $result[] = $byDate[$d] ?? ['date' => $d, 'total' => 0.0, 'count' => 0];
            $current->addDay();
        }
        return $result;
    }

    /**
     * Dernières ventes et bons d'achat pour le fil d'activité du dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRecentActivities(string $shopId, int $limit = 8): array
    {
        $items = [];

        $sales = SaleModel::query()
            ->where('shop_id', $shopId)
            ->completed()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'customer_name', 'total_amount', 'currency', 'created_at']);

        foreach ($sales as $sale) {
            $createdAt = $sale->created_at ? Carbon::parse($sale->created_at) : Carbon::now();
            $items[] = [
                'id' => 'sale-'.$sale->id,
                'type' => 'sale',
                'name' => $sale->customer_name ?: 'Client comptoir',
                'reference' => '#'.strtoupper(substr((string) $sale->id, 0, 8)),
                'time' => $createdAt->format('H:i'),
                'amount' => (float) $sale->total_amount,
                'currency' => (string) ($sale->currency ?? 'USD'),
                'sort_at' => $createdAt->timestamp,
            ];
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('gc_purchases')) {
            $purchases = PurchaseModel::query()
                ->with('supplier:id,name')
                ->where('shop_id', $shopId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['id', 'supplier_id', 'total_amount', 'currency', 'status', 'created_at']);

            foreach ($purchases as $purchase) {
                $createdAt = $purchase->created_at ? Carbon::parse($purchase->created_at) : Carbon::now();
                $supplierName = $purchase->supplier?->name ?? 'Fournisseur';
                $items[] = [
                    'id' => 'purchase-'.$purchase->id,
                    'type' => 'purchase',
                    'name' => $supplierName,
                    'reference' => '#'.strtoupper(substr((string) $purchase->id, 0, 8)),
                    'time' => $createdAt->format('H:i'),
                    'amount' => -1 * abs((float) $purchase->total_amount),
                    'currency' => (string) ($purchase->currency ?? 'USD'),
                    'sort_at' => $createdAt->timestamp,
                ];
            }
        }

        usort($items, fn (array $a, array $b) => ($b['sort_at'] ?? 0) <=> ($a['sort_at'] ?? 0));

        return array_values(array_map(function (array $row) {
            unset($row['sort_at']);

            return $row;
        }, array_slice($items, 0, $limit)));
    }

    /**
     * Répartition du stock pour graphique (out_of_stock, low_stock, medium_stock, good_stock).
     * @param array<string, int> $stockStatus
     * @return array<int, array{name: string, value: int, fill: string}>
     */
    private function getStockDistributionChartData(array $stockStatus): array
    {
        $labels = [
            'out_of_stock' => ['label' => 'Rupture', 'fill' => '#ef4444'],
            'low_stock' => ['label' => 'Stock bas', 'fill' => '#f97316'],
            'medium_stock' => ['label' => 'Stock moyen', 'fill' => '#eab308'],
            'good_stock' => ['label' => 'Bon stock', 'fill' => '#22c55e'],
        ];
        $result = [];
        foreach ($labels as $key => $config) {
            $value = (int) ($stockStatus[$key] ?? 0);
            if ($value > 0) {
                $result[] = [
                    'name' => $config['label'],
                    'value' => $value,
                    'fill' => $config['fill'],
                ];
            }
        }
        return $result;
    }
}

