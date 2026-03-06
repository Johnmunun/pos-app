<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
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

        $salesTodayTotal = (float) SaleModel::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');
        $salesTodayCount = (int) SaleModel::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart)
            ->count();

        $salesLast7Total = (float) SaleModel::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $last7)
            ->sum('total_amount');
        $salesLast7Count = (int) SaleModel::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $last7)
            ->count();

        $purchasesTodayTotal = (float) PurchaseModel::where('shop_id', $shopId)
            ->where('status', 'received')
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');
        $purchasesTodayCount = (int) PurchaseModel::where('shop_id', $shopId)
            ->where('status', 'received')
            ->where('created_at', '>=', $todayStart)
            ->count();

        $purchasesLast7Total = (float) PurchaseModel::where('shop_id', $shopId)
            ->where('status', 'received')
            ->where('created_at', '>=', $last7)
            ->sum('total_amount');
        $purchasesLast7Count = (int) PurchaseModel::where('shop_id', $shopId)
            ->where('status', 'received')
            ->where('created_at', '>=', $last7)
            ->count();

        // Données pour le graphique sur 7 jours (ventes + achats)
        $chartDates = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $chartDates[$date] = [
                'date' => $date,
                'sales_total' => 0.0,
                'purchases_total' => 0.0,
            ];
        }

        $salesRows = SaleModel::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $last7)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($salesRows as $row) {
            $date = (string) $row->date;
            if (isset($chartDates[$date])) {
                $chartDates[$date]['sales_total'] = (float) $row->total;
            }
        }

        $purchaseRows = PurchaseModel::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->where('shop_id', $shopId)
            ->where('status', 'received')
            ->where('created_at', '>=', $last7)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($purchaseRows as $row) {
            $date = (string) $row->date;
            if (isset($chartDates[$date])) {
                $chartDates[$date]['purchases_total'] = (float) $row->total;
            }
        }

        $chartLast7 = array_values($chartDates);

        $currency = 'USD';

        return Inertia::render('Commerce/Dashboard/Index', [
            'dashboard' => [
                'currency' => $currency,
                'sales_today' => [
                    'total' => round($salesTodayTotal, 2),
                    'count' => $salesTodayCount,
                ],
                'sales_last_7' => [
                    'total' => round($salesLast7Total, 2),
                    'count' => $salesLast7Count,
                ],
                'purchases_today' => [
                    'total' => round($purchasesTodayTotal, 2),
                    'count' => $purchasesTodayCount,
                ],
                'purchases_last_7' => [
                    'total' => round($purchasesLast7Total, 2),
                    'count' => $purchasesLast7Count,
                ],
                'chart_last_7' => $chartLast7,
            ],
        ]);
    }
}

