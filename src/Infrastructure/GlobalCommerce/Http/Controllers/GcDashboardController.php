<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;
use Carbon\Carbon;

class GcDashboardController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $depotId = $request->session()->get('current_depot_id');

        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', (int) $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }

        if ($user->tenant_id) {
            return (string) $user->tenant_id;
        }

        abort(403, 'Shop ID not found.');
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

        // Stats stock
        $products = $productModel::where('shop_id', $shopId)->where('is_active', true)->get();
        $totalValue = 0.0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $stockStatus = ['out_of_stock' => 0, 'low_stock' => 0, 'medium_stock' => 0, 'good_stock' => 0];
        
        foreach ($products as $product) {
            $stock = (float) ($product->stock ?? 0);
            $minStock = (float) ($product->minimum_stock ?? 0);
            $purchasePrice = (float) ($product->purchase_price_amount ?? 0);
            
            $totalValue += $purchasePrice * $stock;
            
            if ($stock <= 0) {
                $outOfStockCount++;
                $stockStatus['out_of_stock']++;
            } elseif ($stock <= $minStock) {
                $lowStockCount++;
                $stockStatus['low_stock']++;
            } elseif ($stock <= $minStock * 2) {
                $stockStatus['medium_stock']++;
            } else {
                $stockStatus['good_stock']++;
            }
        }

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

        // Stats ventes
        $salesTodayTotal = (float) SaleModel::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');
        $salesTodayCount = (int) SaleModel::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart)
            ->count();

        // Période pour graphique
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
            ->where('status', 'completed')
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
            ->where('status', 'completed')
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

