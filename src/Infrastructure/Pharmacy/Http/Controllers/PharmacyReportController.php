<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\PurchaseOrderModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use Src\Infrastructure\Pharmacy\Models\SaleLineModel;
use Src\Infrastructure\Pharmacy\Models\StockMovementModel;

/**
 * Contrôleur des rapports Pharmacy.
 * Requêtes optimisées avec cache court (5 min).
 */
class PharmacyReportController
{
    private const CACHE_TTL_SECONDS = 300;

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = null;
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && Schema::hasTable('shops')) {
            $shopByDepot = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }
        if ($shopId === null) {
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel?->isRoot() ?? false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $depotId = $request->session()->get('current_depot_id');
        $cacheKey = 'pharmacy.reports.' . $shopId . '.' . ($depotId ?? '') . '.' . $from . '.' . $to;
        $report = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($shopId, $from, $to) {
            return $this->buildReport($shopId, $from, $to);
        });

        return Inertia::render('Pharmacy/Reports/Index', [
            'report' => $report,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    /**
     * Retourne les données du rapport pour la période (from/to) de la requête.
     * Utilisé par l'export PDF/Excel.
     *
     * @return array<string, mixed>
     */
    public function getReportData(Request $request): array
    {
        $shopId = $this->getShopId($request);
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $cacheKey = 'pharmacy.reports.' . $shopId . '.' . $from . '.' . $to;
        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($shopId, $from, $to) {
            return $this->buildReport($shopId, $from, $to);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(string $shopId, string $from, string $to): array
    {
        $fromDate = $from . ' 00:00:00';
        $toDate = $to . ' 23:59:59';

        $salesStats = SaleModel::where('shop_id', $shopId)
            ->where('status', 'COMPLETED')
            ->whereBetween('completed_at', [$fromDate, $toDate])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        $salesByDay = SaleModel::where('shop_id', $shopId)
            ->where('status', 'COMPLETED')
            ->whereBetween('completed_at', [$fromDate, $toDate])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();

        $productStats = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(price_amount * stock), 0) as stock_value')
            ->first();

        $lowStockCount = 0;
        if (Schema::hasColumn('pharmacy_products', 'minimum_stock')) {
            $lowStockCount = ProductModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('minimum_stock')
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->count();
        }

        $purchasesStats = PurchaseOrderModel::where('shop_id', $shopId)
            ->whereIn('status', ['RECEIVED', 'PARTIALLY_RECEIVED'])
            ->whereNotNull('received_at')
            ->whereBetween('received_at', [$fromDate, $toDate])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        $purchasesByDay = PurchaseOrderModel::where('shop_id', $shopId)
            ->whereIn('status', ['RECEIVED', 'PARTIALLY_RECEIVED'])
            ->whereNotNull('received_at')
            ->whereBetween('received_at', [$fromDate, $toDate])
            ->selectRaw('DATE(received_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(DB::raw('DATE(received_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();

        $movementsStats = StockMovementModel::where('shop_id', $shopId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw("
                SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END) as qty_in,
                SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END) as qty_out,
                SUM(CASE WHEN type = 'ADJUSTMENT' THEN quantity ELSE 0 END) as qty_adj,
                COUNT(*) as total_ops
            ")
            ->first();

        // Analyse par produit : qté vendue, CA, coût (si disponible), bénéfice, marge %
        $productSalesQuery = SaleLineModel::query()
            ->join('pharmacy_sales', 'pharmacy_sale_lines.sale_id', '=', 'pharmacy_sales.id')
            ->where('pharmacy_sales.shop_id', $shopId)
            ->where('pharmacy_sales.status', 'COMPLETED')
            ->whereBetween('pharmacy_sales.completed_at', [$fromDate, $toDate])
            ->groupBy('pharmacy_sale_lines.product_id')
            ->selectRaw('
                pharmacy_sale_lines.product_id,
                SUM(pharmacy_sale_lines.quantity) as qty_sold,
                COALESCE(SUM(pharmacy_sale_lines.line_total_amount), 0) as revenue
            ');
        $productSalesRows = $productSalesQuery->get();
        $productIds = $productSalesRows->pluck('product_id')->unique()->filter()->values()->all();
        $productsById = $productIds !== [] ? ProductModel::whereIn('id', $productIds)->get()->keyBy('id') : collect();
        $products_analysis = [];
        foreach ($productSalesRows as $row) {
            $product = $productsById->get($row->product_id);
            $qtySold = (int) $row->qty_sold;
            $revenue = (float) $row->revenue;
            $costAmount = $product && isset($product->cost_amount) && $product->cost_amount !== null ? (float) $product->cost_amount : null;
            $cost = $costAmount !== null ? $costAmount * $qtySold : null;
            $benefit = $cost !== null ? $revenue - $cost : null;
            $margin_percent = ($benefit !== null && $revenue > 0) ? round(($benefit / $revenue) * 100, 1) : null;
            $products_analysis[] = [
                'product_id' => $row->product_id,
                'product_name' => $product ? $product->name : '—',
                'product_code' => $product ? ($product->code ?? '') : '',
                'qty_sold' => $qtySold,
                'revenue' => $revenue,
                'cost' => $cost,
                'benefit' => $benefit,
                'margin_percent' => $margin_percent,
            ];
        }
        // Trier par CA décroissant (plus vendu en valeur)
        usort($products_analysis, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'sales' => [
                'count' => (int) ($salesStats->count ?? 0),
                'total' => (float) ($salesStats->total ?? 0),
                'by_day' => $salesByDay,
            ],
            'purchases' => [
                'count' => (int) ($purchasesStats->count ?? 0),
                'total' => (float) ($purchasesStats->total ?? 0),
                'by_day' => $purchasesByDay,
            ],
            'movements' => [
                'qty_in' => (int) ($movementsStats->qty_in ?? 0),
                'qty_out' => (int) ($movementsStats->qty_out ?? 0),
                'qty_adjustment' => (int) ($movementsStats->qty_adj ?? 0),
                'total_ops' => (int) ($movementsStats->total_ops ?? 0),
            ],
            'stock' => [
                'product_count' => (int) ($productStats->total ?? 0),
                'total_value' => (float) ($productStats->stock_value ?? 0),
                'low_stock_count' => $lowStockCount,
            ],
            'products_analysis' => $products_analysis,
        ];
    }
}
