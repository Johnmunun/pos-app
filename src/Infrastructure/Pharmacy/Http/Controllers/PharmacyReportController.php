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
use Src\Infrastructure\Pharmacy\Models\SaleModel;

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
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
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

        $cacheKey = 'pharmacy.reports.' . $shopId . '.' . $from . '.' . $to;
        $report = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($shopId, $from, $to) {
            return $this->buildReport($shopId, $from, $to);
        });

        return Inertia::render('Pharmacy/Reports/Index', [
            'report' => $report,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
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

        return [
            'sales' => [
                'count' => (int) ($salesStats->count ?? 0),
                'total' => (float) ($salesStats->total ?? 0),
                'by_day' => $salesByDay,
            ],
            'stock' => [
                'product_count' => (int) ($productStats->total ?? 0),
                'total_value' => (float) ($productStats->stock_value ?? 0),
                'low_stock_count' => $lowStockCount,
            ],
        ];
    }
}
