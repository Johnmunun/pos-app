<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
        
        if ($user) {
            try {
                $permissions = $user->permissionCodes();
            } catch (\Exception $e) {
                \Log::error('Error getting user permissions', [
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
                \Log::warning('Error getting shop currency', ['error' => $e->getMessage()]);
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
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
     * Handle Inertia responses.
     */
    public function rootView(Request $request): string
    {
        return parent::rootView($request);
    }

    /**
     * Set the root template that's loaded on the first Inertia page visit.
     */
    public function rootTemplate(Request $request): string
    {
        return parent::rootTemplate($request);
    }
}
