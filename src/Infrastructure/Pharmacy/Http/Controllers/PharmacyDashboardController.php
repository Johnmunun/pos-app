<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Pharmacy\Services\DashboardService;

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
        if ($shopId === null) {
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        $isRoot = method_exists($user, 'isRoot') ? $user->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        $stats = [
            'products' => ['total' => 0, 'active' => 0, 'inactive' => 0],
            'inventory' => ['total_value' => 0, 'low_stock_count' => 0, 'out_of_stock_count' => 0],
            'expiry' => ['expiring_soon_count' => 0, 'expired_count' => 0],
            'alerts' => [],
        ];

        if ($shopId) {
            try {
                $stats = $this->dashboardService->getDashboardStats($shopId);
            } catch (\Throwable $e) {
                Log::warning('Pharmacy dashboard stats error', ['message' => $e->getMessage()]);
            }
        }

        return Inertia::render('Pharmacy/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
