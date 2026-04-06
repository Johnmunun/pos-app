<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Ecommerce\Models\CustomerModel;
use Src\Infrastructure\Ecommerce\Models\CmsMediaModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;
use Carbon\Carbon;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\Ecommerce\Http\Concerns\ResolvesEcommerceInventoryScope;

class DashboardController
{
    use ResolvesEcommerceInventoryScope;

    public function index(Request $request): Response
    {
        $shop = $this->ecommerceInventoryShop($request);
        $shopId = (string) $shop->id;
        $gcShopIds = $this->ecommerceGcShopIds($request, $shop);

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

        // Stockage médias : images produits (GlobalCommerce) + médias CMS
        $productImageCount = (int) GcProductModel::whereIn('shop_id', $gcShopIds)
            ->whereNotNull('image_path')
            ->count();
        $mediaImageCount = (int) CmsMediaModel::where('shop_id', $shopId)
            ->whereNotNull('file_path')
            ->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                  ->orWhere('file_type', 'image')
                  ->orWhere('file_path', 'like', '%.jpg')
                  ->orWhere('file_path', 'like', '%.jpeg')
                  ->orWhere('file_path', 'like', '%.png')
                  ->orWhere('file_path', 'like', '%.webp');
            })
            ->count();
        $totalImages = $productImageCount + $mediaImageCount;
        // La table `users` n'a pas toujours `shop_id` (selon migrations).
        // On utilise `tenant_id` (présent) comme scope boutique/tenant.
        $tenantIdForUsers = $shop->tenant_id ?? $request->user()?->tenant_id;
        $usersCount = $tenantIdForUsers
            ? (int) \App\Models\User::query()->where('tenant_id', $tenantIdForUsers)->count()
            : 1;
        $perUserLimitMb = 100.0;
        $mediaStorageLimitMb = max($perUserLimitMb, $usersCount * $perUserLimitMb);

        $usedBytes = 0;
        try {
            $paths = [];

            // CMS media files
            $cmsPaths = CmsMediaModel::where('shop_id', $shopId)
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->toArray();
            foreach ($cmsPaths as $p) {
                if (is_string($p) && $p !== '') {
                    $paths[] = $p;
                }
            }

            // Product images (main + extras)
            $productRows = GcProductModel::whereIn('shop_id', $gcShopIds)
                ->get(['image_path', 'extra_images']);
            foreach ($productRows as $row) {
                if (!empty($row->image_path) && is_string($row->image_path)) {
                    $paths[] = $row->image_path;
                }
                $extras = $row->extra_images ?? null;
                if (is_array($extras)) {
                    foreach ($extras as $ep) {
                        if (is_string($ep) && $ep !== '') {
                            $paths[] = $ep;
                        }
                    }
                }
            }

            $paths = array_values(array_unique(array_filter($paths)));

            // Prevent huge scans
            $paths = array_slice($paths, 0, 500);

            foreach ($paths as $path) {
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    continue;
                }
                try {
                    if (Storage::disk('public')->exists($path)) {
                        $usedBytes += (int) Storage::disk('public')->size($path);
                    }
                } catch (\Throwable) {
                    // ignore unreadable files
                }
            }
        } catch (\Throwable) {
            $usedBytes = 0;
        }

        $usedMb = $usedBytes > 0 ? round($usedBytes / 1024 / 1024, 2) : 0.0;

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

        $audienceGeo = [
            'enabled' => false,
            'total_visits' => 0,
            'by_country' => [],
            'by_region' => [],
            'top_cities' => [],
        ];
        $user = $request->user();
        $tenantIdForFeatures = $user && $user->tenant_id ? (string) $user->tenant_id : null;
        if (
            $tenantIdForFeatures
            && Schema::hasTable('ecommerce_storefront_visits')
            && app(FeatureLimitService::class)->isFeatureEnabled($tenantIdForFeatures, 'analytics.advanced')
        ) {
            $audienceGeo['enabled'] = true;
            $audienceGeo['total_visits'] = (int) DB::table('ecommerce_storefront_visits')
                ->where('shop_id', $shopId)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $countryRows = DB::table('ecommerce_storefront_visits')
                ->where('shop_id', $shopId)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('country_code, COUNT(*) as visits')
                ->groupBy('country_code')
                ->orderByDesc('visits')
                ->limit(24)
                ->get();

            $audienceGeo['by_country'] = $countryRows
                ->map(fn ($r) => [
                    'country_code' => $r->country_code,
                    'visits' => (int) $r->visits,
                ])
                ->values()
                ->toArray();

            $regionRows = DB::table('ecommerce_storefront_visits')
                ->where('shop_id', $shopId)
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('region_name')
                ->where('region_name', '!=', '')
                ->selectRaw('region_name, country_code, COUNT(*) as visits')
                ->groupBy('region_name', 'country_code')
                ->orderByDesc('visits')
                ->limit(18)
                ->get();

            $audienceGeo['by_region'] = $regionRows
                ->map(fn ($r) => [
                    'region_name' => (string) $r->region_name,
                    'country_code' => $r->country_code,
                    'visits' => (int) $r->visits,
                ])
                ->values()
                ->toArray();

            $cityRows = DB::table('ecommerce_storefront_visits')
                ->where('shop_id', $shopId)
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->selectRaw('city, region_name, country_code, COUNT(*) as visits')
                ->groupBy('city', 'region_name', 'country_code')
                ->orderByDesc('visits')
                ->limit(12)
                ->get();

            $audienceGeo['top_cities'] = $cityRows
                ->map(fn ($r) => [
                    'city' => (string) $r->city,
                    'region_name' => $r->region_name !== null ? (string) $r->region_name : null,
                    'country_code' => $r->country_code,
                    'visits' => (int) $r->visits,
                ])
                ->values()
                ->toArray();
        }

        return Inertia::render('Ecommerce/Dashboard', [
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'audienceGeo' => $audienceGeo,
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
                'media_storage' => [
                    'images_count' => $totalImages,
                    'used_mb' => $usedMb,
                    'limit_mb' => $mediaStorageLimitMb,
                    'users_count' => $usersCount,
                    'per_user_limit_mb' => $perUserLimitMb,
                ],
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
