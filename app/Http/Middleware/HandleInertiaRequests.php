<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        // Récupérer les permissions de l'utilisateur
        $permissions = [];
        $shopCurrency = 'CDF'; // Devise par défaut (Franc Congolais)
        $shopCurrencies = []; // Liste des devises configurées
        
        $tenantSector = null;
        $currentDepot = null;
        $depots = [];
        if ($user) {
            try {
                $permissions = $user->permissionCodes();
                
                // Récupérer le secteur du tenant
                if ($user->tenant_id) {
                    $tenant = \App\Models\Tenant::find($user->tenant_id);
                    $tenantSector = $tenant?->sector;

                    // Dépôts du tenant (si table existe)
                    if (\Illuminate\Support\Facades\Schema::hasTable('depots')) {
                        $depots = \App\Models\Depot::where('tenant_id', $user->tenant_id)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get(['id', 'name', 'code'])
                            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])
                            ->values()
                            ->toArray();

                        // Dépôt actuel (session ou premier dépôt si un seul)
                        $depotId = $request->session()->get('current_depot_id');
                        if ($depotId && count($depots) > 0) {
                            $depot = collect($depots)->firstWhere('id', $depotId);
                            if ($depot) {
                                $currentDepot = $depot;
                            }
                        }
                        if (!$currentDepot && count($depots) === 1) {
                            $currentDepot = $depots[0];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error getting user permissions', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $permissions = [];
            }
            
            // Récupérer la devise de la boutique
            try {
                $shopId = $user->shop_id ?? $user->tenant_id;
                if ($shopId) {
                    // Chercher la boutique
                    $shop = \App\Models\Shop::find($shopId);
                    if ($shop && $shop->currency) {
                        $shopCurrency = $shop->currency;
                    }
                    
                    // Récupérer les devises configurées pour le tenant
                    $tenantId = $user->tenant_id ?? $shopId;
                    $currencies = \App\Models\Currency::where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->orderByDesc('is_default')
                        ->orderBy('code')
                        ->get(['id', 'code', 'name', 'symbol', 'is_default']);
                    
                    $shopCurrencies = $currencies->map(fn($c) => [
                        'id' => $c->id,
                        'code' => $c->code,
                        'name' => $c->name,
                        'symbol' => $c->symbol,
                        'is_default' => $c->is_default,
                    ])->toArray();
                    
                    // Si devise par défaut trouvée dans les devises configurées
                    $defaultCurrency = $currencies->firstWhere('is_default', true);
                    if ($defaultCurrency) {
                        $shopCurrency = $defaultCurrency->code;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error getting shop currency', ['error' => $e->getMessage()]);
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
                'tenantSector' => $tenantSector,
                'currentDepot' => $currentDepot ?? null,
                'depots' => $depots ?? [],
                'isImpersonating' => $request->session()->get('impersonate.impersonating', false),
                'originalUserId' => $request->session()->get('impersonate.original_user_id'),
            ],
            'shop' => [
                'currency' => $shopCurrency,
                'currencies' => $shopCurrencies,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
        ];
    }

    /**
     * Set the root template that's loaded on the first Inertia page visit.
     */
    public function rootView(Request $request): string
    {
        return parent::rootView($request);
    }
}
