<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Pharmacy\Services\DashboardService;
use Src\Infrastructure\Pharmacy\Models\SaleModel;

class PharmacyDashboardController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = null;
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shopByDepot = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }
        // Aligner sur SaleController / ProductController : sans dépôt, utiliser shop_id ou tenant_id
        // (les ventes et produits sont souvent enregistrés avec tenant_id comme shop_id)
        if ($shopId === null) {
            $shopId = $user->shop_id !== null && $user->shop_id !== ''
                ? (string) $user->shop_id
                : ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        $isRoot = method_exists($user, 'isRoot') ? $user->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        $stats = [
            'products' => ['total' => 0, 'active' => 0, 'inactive' => 0],
            'inventory' => ['total_value' => 0, 'low_stock_count' => 0, 'out_of_stock_count' => 0, 'stock_status' => []],
            'expiry' => ['expiring_soon_count' => 0, 'expired_count' => 0],
            'alerts' => [],
        ];

        $chartSalesLastDays = [];
        $chartStockDistribution = [];

        $period = (int) $request->input('period', 14);
        if (!in_array($period, [7, 14, 30], true)) {
            $period = 14;
        }
        $dateFrom = $request->input('from');
        $dateTo = $request->input('to');
        $useDateRange = $dateFrom && $dateTo;

        if ($shopId) {
            $effectiveShopId = $shopId;
            $statsLoaded = false;

            $tryShopIds = [$shopId];
            if ($user->tenant_id && (string) $user->tenant_id !== $shopId) {
                $tryShopIds[] = (string) $user->tenant_id;
            }
            $firstShop = $user->tenant_id ? \App\Models\Shop::where('tenant_id', $user->tenant_id)->first() : null;
            if ($firstShop) {
                $sid = (string) $firstShop->id;
                if (!in_array($sid, $tryShopIds, true)) {
                    $tryShopIds[] = $sid;
                }
            }
            foreach ($tryShopIds as $candidateId) {
                try {
                    $stats = $this->dashboardService->getDashboardStats($candidateId);
                    $statsLoaded = true;
                    $effectiveShopId = $candidateId;
                    if (($stats['products']['total'] ?? 0) > 0) {
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Pharmacy dashboard stats error for shop', [
                        'message' => $e->getMessage(),
                        'shop_id' => $candidateId,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            if ($statsLoaded) {
                try {
                    $chartSalesLastDays = $useDateRange
                        ? $this->getSalesChartDataByRange($effectiveShopId, $dateFrom, $dateTo)
                        : $this->getSalesChartData($effectiveShopId, $period);
                    $chartStockDistribution = $this->getStockDistributionChartData($stats['inventory']['stock_status'] ?? []);
                } catch (\Throwable $e) {
                    Log::warning('Pharmacy dashboard charts error', ['message' => $e->getMessage()]);
                }
            }
        }

        return Inertia::render('Pharmacy/Dashboard', [
            'stats' => $stats,
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
            ->where('status', 'COMPLETED')
            ->whereBetween(DB::raw('COALESCE(completed_at, created_at)'), [$start, $end])
            ->selectRaw('DATE(COALESCE(completed_at, created_at)) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(DB::raw('DATE(COALESCE(completed_at, created_at))'))
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
        $start = \Carbon\Carbon::parse($dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        $end = \Carbon\Carbon::parse($dateTo)->endOfDay()->format('Y-m-d H:i:s');

        $rows = SaleModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'COMPLETED')
            ->whereBetween(DB::raw('COALESCE(completed_at, created_at)'), [$start, $end])
            ->selectRaw('DATE(COALESCE(completed_at, created_at)) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy(DB::raw('DATE(COALESCE(completed_at, created_at))'))
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
        $current = \Carbon\Carbon::parse($dateFrom);
        $endDate = \Carbon\Carbon::parse($dateTo);
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
