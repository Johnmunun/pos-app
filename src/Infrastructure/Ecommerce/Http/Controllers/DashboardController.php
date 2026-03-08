<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Ecommerce\Models\CustomerModel;
use Carbon\Carbon;

class DashboardController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $tenantId = $user->tenant_id;
        if (!$tenantId) {
            abort(403, 'Tenant ID not found.');
        }

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $last7 = $now->copy()->subDays(7)->startOfDay();

        // Stats commandes
        $ordersTotal = (int) OrderModel::where('tenant_id', $tenantId)->count();
        $ordersToday = (int) OrderModel::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $todayStart)
            ->count();
        $ordersPending = (int) OrderModel::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->count();
        $ordersCompleted = (int) OrderModel::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->count();

        // Stats clients
        $customersTotal = (int) CustomerModel::where('tenant_id', $tenantId)->count();
        $customersActive = (int) CustomerModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        // Revenus
        $revenueToday = (float) OrderModel::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount');
        $revenueLast7Days = (float) OrderModel::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $last7)
            ->sum('total_amount');

        // Graphique commandes (7 derniers jours)
        $chartOrders = $this->getOrdersChartData($tenantId, 7);

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
        ]);
    }

    /**
     * Commandes par jour sur les N derniers jours (pour graphique).
     * @return array<int, array{date: string, count: int, revenue: float}>
     */
    private function getOrdersChartData(string $tenantId, int $days): array
    {
        $start = now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s');
        $end = now()->endOfDay()->format('Y-m-d H:i:s');

        $rows = OrderModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
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
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $result[] = $byDate[$d] ?? ['date' => $d, 'count' => 0, 'revenue' => 0.0];
        }
        return $result;
    }
}
