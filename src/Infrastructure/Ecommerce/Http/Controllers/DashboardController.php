<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Ecommerce\Models\CustomerModel;
use Carbon\Carbon;

class DashboardController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $userModel = \App\Models\User::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

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

        $fromInput = $request->input('from');
        $toInput = $request->input('to');
        $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $toInput ? Carbon::parse($toInput)->endOfDay() : now()->endOfDay();
        if ($from > $to) {
            $from = $to->copy()->subDays(30)->startOfDay();
        }

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $last7 = $now->copy()->subDays(7)->startOfDay();

        // Stats commandes (ecommerce_orders utilise shop_id)
        $ordersTotal = (int) OrderModel::where('shop_id', $shopId)->count();
        $ordersToday = (int) OrderModel::where('shop_id', $shopId)
            ->where('created_at', '>=', $todayStart)
            ->count();
        $ordersPending = (int) OrderModel::where('shop_id', $shopId)
            ->where('status', 'pending')
            ->count();
        $ordersCompleted = (int) OrderModel::where('shop_id', $shopId)
            ->whereIn('status', ['shipped', 'delivered'])
            ->count();

        // Stats clients (ecommerce_customers utilise shop_id)
        $customersTotal = 0;
        $customersActive = 0;
        if (Schema::hasTable('ecommerce_customers')) {
            $customersTotal = (int) CustomerModel::where('shop_id', $shopId)->count();
            $customersActive = (int) CustomerModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->count();
        }

        // Revenus (payé = payment_status paid)
        $revenueToday = (float) OrderModel::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');
        $revenueLast7Days = (float) OrderModel::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $last7)
            ->sum('total_amount');

        // Graphique commandes (période filtrée ou 30 derniers jours)
        $chartOrders = $this->getOrdersChartDataFiltered($shopId, $from, $to);

        // Répartition statuts commandes (pour PieChart)
        $chartOrderStatus = $this->getOrderStatusDistributionChartData($shopId);

        // Alerts
        $alerts = [];
        if ($ordersPending > 10) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$ordersPending} commande(s) en attente",
                'priority' => 'Moyenne',
            ];
        }

        return Inertia::render('Ecommerce/Dashboard', [
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'stats' => [
                'orders' => [
                    'total' => $ordersTotal,
                    'today' => $ordersToday,
                    'pending' => $ordersPending,
                    'completed' => $ordersCompleted,
                ],
                'customers' => [
                    'total' => $customersTotal,
                    'active' => $customersActive,
                ],
                'revenue' => [
                    'today' => round($revenueToday, 2),
                    'last_7_days' => round($revenueLast7Days, 2),
                ],
                'alerts' => $alerts,
            ],
            'chartOrders' => $chartOrders,
            'chartOrderStatus' => $chartOrderStatus,
        ]);
    }

    /**
     * Commandes par jour sur une période (pour graphique).
     * @return array<int, array{date: string, count: int, revenue: float}>
     */
    private function getOrdersChartDataFiltered(string $shopId, Carbon $from, Carbon $to): array
    {
        $rows = OrderModel::query()
            ->where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $date = $r->getAttribute('date');
            $count = (int) $r->getAttribute('count');
            $revenue = (float) $r->getAttribute('revenue');
            $byDate[$date] = ['date' => $date, 'count' => $count, 'revenue' => $revenue];
        }

        $result = [];
        $current = $from->copy();
        while ($current <= $to) {
            $d = $current->format('Y-m-d');
            $result[] = $byDate[$d] ?? ['date' => $d, 'count' => 0, 'revenue' => 0.0];
            $current->addDay();
        }
        return $result;
    }

    /**
     * Répartition par statut pour graphique camembert.
     * @return array<int, array{name: string, value: int, fill: string}>
     */
    private function getOrderStatusDistributionChartData(string $shopId): array
    {
        $labels = [
            'pending' => ['label' => 'En attente', 'fill' => '#f59e0b'],
            'confirmed' => ['label' => 'Confirmé', 'fill' => '#3b82f6'],
            'processing' => ['label' => 'En cours', 'fill' => '#8b5cf6'],
            'shipped' => ['label' => 'Expédié', 'fill' => '#06b6d4'],
            'delivered' => ['label' => 'Livré', 'fill' => '#22c55e'],
            'cancelled' => ['label' => 'Annulé', 'fill' => '#ef4444'],
        ];

        $rows = OrderModel::query()
            ->where('shop_id', $shopId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $result = [];
        foreach ($labels as $key => $config) {
            $value = (int) ($rows[$key] ?? 0);
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
