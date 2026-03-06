<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleLineModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockMovementModel;

/**
 * Contrôleur des rapports d'activité - Module Global Commerce.
 * Rapport global : ventes, achats, mouvements stock, analyse par produit.
 */
class GcReportController
{
    /**
     * @return array{0: string, 1: \App\Models\Shop|null}
     */
    private function getShopIdAndShop(Request $request): array
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
                return [(string) $shop->id, $shop];
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            $shop = \App\Models\Shop::find($user->shop_id);
            return [(string) $user->shop_id, $shop];
        }

        if ($user->tenant_id) {
            $shop = \App\Models\Shop::find($user->tenant_id);
            return [(string) $user->tenant_id, $shop];
        }

        abort(403, 'Shop ID not found.');
    }

    private function getShopId(Request $request): string
    {
        [$shopId] = $this->getShopIdAndShop($request);
        return $shopId;
    }

    public function index(Request $request): Response
    {
        [$shopId, $shop] = $this->getShopIdAndShop($request);
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $report = $this->buildReport($shopId, $from, $to);
        $report['currency'] = $shop?->currency ?? 'CDF';

        return Inertia::render('Commerce/Reports/Index', [
            'report' => $report,
            'filters' => ['from' => $from, 'to' => $to],
            'routePrefix' => 'commerce',
        ]);
    }

    /**
     * Données du rapport pour export PDF/Excel.
     *
     * @return array<string, mixed>
     */
    public function getReportData(Request $request): array
    {
        [$shopId, $shop] = $this->getShopIdAndShop($request);
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $report = $this->buildReport($shopId, $from, $to);
        $report['currency'] = $shop?->currency ?? 'CDF';

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(string $shopId, string $from, string $to): array
    {
        $fromDate = $from . ' 00:00:00';
        $toDate = $to . ' 23:59:59';
        $shopIdInt = (int) $shopId;

        // Ventes (COMPLETED)
        $salesQuery = SaleModel::where('shop_id', $shopIdInt)
            ->whereRaw('UPPER(status) = ?', ['COMPLETED'])
            ->whereBetween('created_at', [$fromDate, $toDate]);

        $salesStats = $salesQuery->clone()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        $salesByDay = $salesQuery->clone()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();

        // Achats reçus (status received)
        $purchasesQuery = PurchaseModel::where('shop_id', $shopIdInt)
            ->whereRaw('LOWER(status) = ?', ['received'])
            ->whereNotNull('received_at')
            ->whereBetween('received_at', [$fromDate, $toDate]);

        $purchasesStats = $purchasesQuery->clone()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        $purchasesByDay = $purchasesQuery->clone()
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

        // Mouvements de stock (gc_stock_movements : IN, OUT)
        $movementsStats = (object) ['qty_in' => 0, 'qty_out' => 0, 'total_ops' => 0];
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_stock_movements')) {
            $movementsStats = GcStockMovementModel::where('shop_id', $shopIdInt)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END), 0) as qty_in,
                    COALESCE(SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END), 0) as qty_out,
                    COUNT(*) as total_ops
                ")
                ->first() ?? $movementsStats;
        }

        // Stock actuel (produits actifs)
        $productStats = ProductModel::where('shop_id', $shopIdInt)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(purchase_price_amount * stock), 0) as stock_value')
            ->first();

        $lowStockCount = ProductModel::where('shop_id', $shopIdInt)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        // Analyse par produit (gc_sale_lines)
        $productSalesRows = SaleLineModel::query()
            ->join('gc_sales', 'gc_sale_lines.sale_id', '=', 'gc_sales.id')
            ->where('gc_sales.shop_id', $shopIdInt)
            ->whereRaw('UPPER(gc_sales.status) = ?', ['COMPLETED'])
            ->whereBetween('gc_sales.created_at', [$fromDate, $toDate])
            ->groupBy('gc_sale_lines.product_id')
            ->selectRaw('
                gc_sale_lines.product_id,
                SUM(gc_sale_lines.quantity) as qty_sold,
                COALESCE(SUM(gc_sale_lines.subtotal), 0) as revenue
            ')
            ->get();

        $productIds = $productSalesRows->pluck('product_id')->unique()->filter()->values()->all();
        $productsById = $productIds !== [] ? ProductModel::whereIn('id', $productIds)->get()->keyBy('id') : collect();

        $products_analysis = [];
        foreach ($productSalesRows as $row) {
            $product = $productsById->get($row->product_id);
            $qtySold = (float) $row->qty_sold;
            $revenue = (float) $row->revenue;
            $costAmount = $product?->purchase_price_amount ?? null;
            $cost = $costAmount !== null ? (float) $costAmount * $qtySold : null;
            $benefit = $cost !== null ? $revenue - $cost : null;
            $margin_percent = ($benefit !== null && $revenue > 0) ? round(($benefit / $revenue) * 100, 1) : null;

            $products_analysis[] = [
                'product_id' => $row->product_id,
                'product_name' => $product?->name ?? '—',
                'product_code' => $product ? ($product->sku ?? $product->barcode ?? '') : '',
                'qty_sold' => (int) $qtySold,
                'revenue' => $revenue,
                'cost' => $cost,
                'benefit' => $benefit,
                'margin_percent' => $margin_percent,
            ];
        }
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
                'qty_in' => (int) ($movementsStats?->qty_in ?? 0),
                'qty_out' => (int) ($movementsStats?->qty_out ?? 0),
                'qty_adjustment' => 0,
                'total_ops' => (int) ($movementsStats?->total_ops ?? 0),
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
