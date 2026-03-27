<?php

namespace Src\Infrastructure\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CrmDashboardController extends Controller
{
    private function resolveShopForRoot(Request $request): ?Shop
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
            return null;
        }

        $requestedShopId = (int) ($request->query('shop_id') ?? $request->input('shop_id') ?? 0);
        $sessionShopId = (int) ($request->session()->get('current_crm_shop_id') ?? 0);
        $shopId = $requestedShopId > 0 ? $requestedShopId : $sessionShopId;

        if ($shopId > 0) {
            $shop = Shop::find($shopId);
            if ($shop) {
                $request->session()->put('current_crm_shop_id', $shopId);
                return $shop;
            }
        }

        return null;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $shop = $this->resolveShopForRoot($request);
        if (!$shop && $user) {
            $shopId = $user->shop_id ?? $user->tenant_id;
            if ($shopId) {
                $shop = Shop::find($shopId);
            }
        }

        $isRoot = $user && method_exists($user, 'isRoot') && $user->isRoot();
        $shopsForRoot = [];
        if ($isRoot) {
            $shopsForRoot = Shop::query()
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name'])
                ->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->name])
                ->toArray();
        }

        $storefrontConfig = is_array($shop?->ecommerce_storefront_config) ? $shop->ecommerce_storefront_config : [];
        $whatsapp = [
            'number' => $storefrontConfig['whatsapp_number'] ?? null,
            'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
        ];

        $days = (int) ($request->query('days') ?? 7);
        if ($days < 1) $days = 7;
        if ($days > 30) $days = 30;

        $since = Carbon::now()->subDays($days)->startOfDay();

        $totalActions = DB::table('user_activities')
            ->where('created_at', '>=', $since)
            ->count();

        $uniqueUsers = DB::table('user_activities')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $actionsByModule = DB::table('user_activities')
            ->select('module', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $since)
            ->groupBy('module')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['module' => $r->module ?? 'autre', 'total' => (int) $r->total])
            ->toArray();

        $topRoutes = DB::table('user_activities')
            ->select('route', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $since)
            ->whereNotNull('route')
            ->groupBy('route')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['route' => (string) $r->route, 'total' => (int) $r->total])
            ->toArray();

        $recentActions = DB::table('user_activities')
            ->leftJoin('users', 'users.id', '=', 'user_activities.user_id')
            ->select(['user_activities.id', 'user_activities.action', 'user_activities.module', 'user_activities.route', 'user_activities.ip_address', 'user_activities.created_at', 'users.name as user_name'])
            ->where('user_activities.created_at', '>=', $since)
            ->orderByDesc('user_activities.created_at')
            ->limit(30)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'user_name' => $r->user_name ? (string) $r->user_name : null,
                'action' => (string) $r->action,
                'module' => $r->module ? (string) $r->module : null,
                'route' => $r->route ? (string) $r->route : null,
                'ip_address' => $r->ip_address ? (string) $r->ip_address : null,
                'created_at' => $r->created_at ? Carbon::parse($r->created_at)->toDateTimeString() : null,
            ])
            ->toArray();

        // Support KPIs (if support tables exist)
        $supportCounts = [
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'closed' => 0,
        ];
        try {
            $support = DB::table('support_tickets')
                ->select('status', DB::raw('count(*) as total'))
                ->where('created_at', '>=', $since)
                ->groupBy('status')
                ->get();
            foreach ($support as $row) {
                $k = (string) $row->status;
                if (array_key_exists($k, $supportCounts)) {
                    $supportCounts[$k] = (int) $row->total;
                }
            }
        } catch (\Throwable $e) {
            // ignore if table missing
        }

        return Inertia::render('Admin/CrmDashboard', [
            'selectedShopId' => $shop?->id ? (int) $shop->id : null,
            'shops' => $shopsForRoot,
            'whatsapp' => $whatsapp,
            'range' => [
                'days' => $days,
                'since' => $since->toDateString(),
            ],
            'kpis' => [
                'totalActions' => (int) $totalActions,
                'uniqueUsers' => (int) $uniqueUsers,
                'support' => $supportCounts,
            ],
            'actionsByModule' => $actionsByModule,
            'topRoutes' => $topRoutes,
            'recentActions' => $recentActions,
        ]);
    }

    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $validated = $request->validate([
            'whatsapp_number' => 'nullable|string|max:30',
            'whatsapp_support_enabled' => 'sometimes|boolean',
        ]);

        $shop = $this->resolveShopForRoot($request);
        if (!$shop) {
            $shopId = $user->shop_id ?? $user->tenant_id;
            $shop = $shopId ? Shop::find((int) $shopId) : null;
        }

        if (!$shop) {
            abort(422, 'Shop introuvable. Sélectionnez une boutique.');
        }

        $config = is_array($shop->ecommerce_storefront_config) ? $shop->ecommerce_storefront_config : [];

        $config['whatsapp_number'] = $validated['whatsapp_number'] ?? null;
        $config['whatsapp_support_enabled'] = (bool) ($validated['whatsapp_support_enabled'] ?? false);

        $shop->ecommerce_storefront_config = $config;
        $shop->save();

        return back()->with('success', 'WhatsApp support mis à jour.');
    }
}

