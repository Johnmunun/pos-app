<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;
use Src\Application\Billing\Services\FeatureLimitService;

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
        $receiptAutoPrint = false;
        $shopLogoUrl = null;
        $appLogoUrl = null;
        
        $tenantSector = null;
        $currentDepot = null;
        $depots = [];
        if ($user) {
            try {
                $permissions = $user->permissionCodes();
            } catch (\Exception $e) {
                Log::error('Error getting user permissions', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $permissions = [];
            }

            try {
                if ($user->tenant_id) {
                    $tenant = \App\Models\Tenant::find($user->tenant_id);
                    $tenantSector = $tenant?->sector;

                    // Si le secteur du tenant n'est pas encore défini,
                    // on essaie de le déduire à partir des permissions de module
                    if (!$tenantSector && !empty($permissions)) {
                        if (in_array('module.hardware', $permissions, true)) {
                            $tenantSector = 'hardware';
                        } elseif (in_array('module.pharmacy', $permissions, true)) {
                            $tenantSector = 'pharmacy';
                        } elseif (in_array('module.commerce', $permissions, true)) {
                            $tenantSector = 'commerce';
                        }
                    }
                    // Secteurs Kiosque, Supermarché, Boucherie, Autre = module Global Commerce
                    if (in_array($tenantSector, ['kiosk', 'supermarket', 'butchery', 'other'], true)) {
                        $tenantSector = 'commerce';
                    }

                    if (\Illuminate\Support\Facades\Schema::hasTable('depots')) {
                        $depotsQuery = \App\Models\Depot::where('tenant_id', $user->tenant_id)
                            ->where('is_active', true)
                            ->orderBy('name');

                        try {
                            if (method_exists($user, 'isRoot') && !$user->isRoot() && method_exists($user, 'depots')) {
                                // Qualifier la colonne pour éviter les ambiguïtés avec la table pivot user_depot
                                $userDepotIds = $user->depots()->pluck('depots.id')->map(fn ($id) => (int) $id)->toArray();
                                if (!empty($userDepotIds)) {
                                    $depotsQuery->whereIn('id', $userDepotIds);
                                }
                            }
                        } catch (\Throwable $e) {
                            Log::debug('Depot filter by user skipped', ['error' => $e->getMessage()]);
                        }

                        $depots = $depotsQuery->get(['id', 'name', 'code'])
                            ->map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name, 'code' => $d->code ?? ''])
                            ->values()
                            ->toArray();

                        $depotId = $request->session()->get('current_depot_id');
                        $depotId = $depotId !== null ? (int) $depotId : null;

                        if ($depotId && count($depots) > 0) {
                            $depot = collect($depots)->firstWhere('id', $depotId);
                            if ($depot) {
                                $currentDepot = $depot;
                            }
                        }
                        if (!$currentDepot && count($depots) === 1) {
                            $currentDepot = $depots[0];
                            $request->session()->put('current_depot_id', $currentDepot['id']);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error loading depots for Inertia', ['error' => $e->getMessage()]);
            }
            
            // Récupérer la devise de la boutique
            try {
                // Pour Commerce : prioriser la boutique liée au dépôt courant (cohérent avec GcReportController)
                $currencyFromDepotShop = false;
                if ($currentDepot && $tenantSector === 'commerce' && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
                    $shopByDepot = \App\Models\Shop::where('depot_id', (int) $currentDepot['id'])
                        ->where('tenant_id', $user->tenant_id)
                        ->first();
                    if ($shopByDepot && $shopByDepot->currency) {
                        $shopCurrency = $shopByDepot->currency;
                        $currencyFromDepotShop = true;
                    }
                }

                $shopId = $user->shop_id ?? $user->tenant_id;
                    if ($shopId) {
                    // Chercher la boutique (sauf si devise déjà obtenue depuis le dépôt Commerce)
                    if (!$currencyFromDepotShop) {
                        $shop = \App\Models\Shop::find($shopId);
                        if ($shop && $shop->currency) {
                            $shopCurrency = $shop->currency;
                        }
                    }

                    // Récupérer les devises configurées pour le tenant
                    $tenantId = $user->tenant_id ?? $shopId;
                    $currencies = \App\Models\Currency::where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->orderByDesc('is_default')
                        ->orderBy('code')
                        ->get(['id', 'code', 'name', 'symbol', 'is_default']);

                    $shopCurrencies = $currencies->map(fn ($c) => [
                        'id' => $c->id,
                        'code' => $c->code,
                        'name' => $c->name,
                        'symbol' => $c->symbol,
                        'is_default' => $c->is_default,
                    ])->toArray();

                    // Si devise par défaut trouvée dans les devises configurées (ne pas écraser si déjà définie par le dépôt Commerce)
                    if (!$currencyFromDepotShop) {
                        $defaultCurrency = $currencies->firstWhere('is_default', true);
                        if ($defaultCurrency) {
                            $shopCurrency = $defaultCurrency->code;
                        }
                    }

                        try {
                            /** @var GetStoreSettingsUseCase $getSettings */
                            $getSettings = app(GetStoreSettingsUseCase::class);
                            $settings = $getSettings->execute((string) $shopId);
                            if ($settings) {
                                $receiptAutoPrint = $settings->isReceiptAutoPrintEnabled();
                                try {
                                    /** @var StoreLogoService $logoService */
                                    $logoService = app(StoreLogoService::class);
                                    $shopLogoUrl = $logoService->getUrl($settings->getLogoPath());
                                } catch (\Throwable $e) {
                                    Log::warning('Error getting store logo for Inertia', ['error' => $e->getMessage()]);
                                }
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Error getting store settings for Inertia', ['error' => $e->getMessage()]);
                        }
                }
            } catch (\Exception $e) {
                Log::warning('Error getting shop currency', ['error' => $e->getMessage()]);
            }
        }

        $featureFlags = [
            'api_payments' => true,
            'analytics_advanced' => true,
        ];
        $billingSummary = null;
        if ($user && $user->tenant_id) {
            try {
                /** @var FeatureLimitService $featureLimitService */
                $featureLimitService = app(FeatureLimitService::class);
                $featureFlags['api_payments'] = $featureLimitService->isFeatureEnabled(
                    (string) $user->tenant_id,
                    'api.payments'
                );
                $featureFlags['analytics_advanced'] = $featureLimitService->isFeatureEnabled(
                    (string) $user->tenant_id,
                    'analytics.advanced'
                );
            } catch (\Throwable $e) {
                Log::debug('Feature flags fallback', ['error' => $e->getMessage()]);
            }

            try {
                if (
                    \Illuminate\Support\Facades\Schema::hasTable('billing_plans')
                    && \Illuminate\Support\Facades\Schema::hasTable('tenant_plan_subscriptions')
                ) {
                    $subscription = \Illuminate\Support\Facades\DB::table('tenant_plan_subscriptions as tps')
                        ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
                        ->where('tps.tenant_id', (string) $user->tenant_id)
                        ->where('tps.status', 'active')
                        ->select(['bp.name as plan_name', 'tps.ends_at', 'tps.trial_ends_at'])
                        ->orderByDesc('tps.id')
                        ->first();

                    $productsConfig = $featureLimitService->getTenantFeatureConfig((string) $user->tenant_id, 'products.max');
                    $usersConfig = $featureLimitService->getTenantFeatureConfig((string) $user->tenant_id, 'users.max');

                    $usersCount = (int) \Illuminate\Support\Facades\DB::table('users')
                        ->where('tenant_id', (string) $user->tenant_id)
                        ->where('type', '!=', 'ROOT')
                        ->count();

                    $shopIds = [];
                    if (\Illuminate\Support\Facades\Schema::hasTable('shops')) {
                        $shopIds = \Illuminate\Support\Facades\DB::table('shops')
                            ->where('tenant_id', (string) $user->tenant_id)
                            ->pluck('id')
                            ->toArray();
                    }

                    $productsCount = 0;
                    if (\Illuminate\Support\Facades\Schema::hasTable('gc_products')) {
                        $productsCount += !empty($shopIds)
                            ? \Illuminate\Support\Facades\DB::table('gc_products')->whereIn('shop_id', $shopIds)->count()
                            : \Illuminate\Support\Facades\DB::table('gc_products')->where('shop_id', (string) $user->tenant_id)->count();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_products')) {
                        $productsCount += !empty($shopIds)
                            ? \Illuminate\Support\Facades\DB::table('pharmacy_products')->whereIn('shop_id', $shopIds)->count()
                            : \Illuminate\Support\Facades\DB::table('pharmacy_products')->where('shop_id', (string) $user->tenant_id)->count();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_products')) {
                        $productsCount += !empty($shopIds)
                            ? \Illuminate\Support\Facades\DB::table('quincaillerie_products')->whereIn('shop_id', $shopIds)->count()
                            : \Illuminate\Support\Facades\DB::table('quincaillerie_products')->where('shop_id', (string) $user->tenant_id)->count();
                    }

                    $billingSummary = [
                        'plan_name' => $subscription?->plan_name ?? 'Plan par defaut',
                        'expires_at' => $subscription?->ends_at ?? $subscription?->trial_ends_at ?? null,
                        'products_used' => (int) $productsCount,
                        'products_limit' => $productsConfig['enabled'] ? $productsConfig['limit'] : 0,
                        'users_used' => (int) $usersCount,
                        'users_limit' => $usersConfig['enabled'] ? $usersConfig['limit'] : 0,
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug('Billing summary fallback', ['error' => $e->getMessage()]);
            }
        }

        // Thème storefront (couleurs primaire/secondaire) — routes storefront ou vitrine publique (sous-domaine)
        $storefrontTheme = null;
        $isStorefrontPath = str_starts_with($request->path(), 'ecommerce/storefront') || $request->attributes->get('storefront_shop') !== null;
        if ($isStorefrontPath) {
            $shop = $request->attributes->get('storefront_shop');
            if (!$shop && $user) {
                $shopId = null;
                if (method_exists($user, 'isRoot') && $user->isRoot()) {
                    $shopId = $request->session()->get('current_storefront_shop_id');
                }
                if (!$shopId) {
                    $shopId = $user->shop_id ?? $user->tenant_id;
                }
                $shop = $shopId ? \App\Models\Shop::find($shopId) : null;
            }
            if ($shop) {
                $config = $shop->ecommerce_storefront_config ?? [];
                if (!is_array($config)) {
                    $config = [];
                }
                $storefrontTheme = [
                    'primary' => $config['theme_primary_color'] ?? '#f59e0b',
                    'secondary' => $config['theme_secondary_color'] ?? '#d97706',
                ];
            }
            if (!$storefrontTheme) {
                $storefrontTheme = ['primary' => '#f59e0b', 'secondary' => '#d97706'];
            }
        }

        // Branding global de l'application (OmniPOS) - logo + visuels hero landing (même pour les invités)
        $heroMainUrl = null;
        $heroDevicesUrl = null;
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');

            $logoPath = 'settings/app/app-logo.png';
            $heroMainPath = 'settings/app/hero-pos-main.png';
            $heroDevicesPath = 'settings/app/hero-pos-devices.png';

            if ($disk->exists($logoPath)) {
                $appLogoUrl = $disk->url($logoPath);
            }

            if ($disk->exists($heroMainPath)) {
                $heroMainUrl = $disk->url($heroMainPath);
            }

            if ($disk->exists($heroDevicesPath)) {
                $heroDevicesUrl = $disk->url($heroDevicesPath);
            }
        } catch (\Throwable $e) {
            Log::debug('Error getting app branding for Inertia', ['error' => $e->getMessage()]);
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
                'featureFlags' => $featureFlags,
                'billingSummary' => $billingSummary,
            ],
            'shop' => [
                'currency' => $shopCurrency,
                'currencies' => $shopCurrencies,
                'receipt_auto_print' => $receiptAutoPrint,
                'logo_url' => $shopLogoUrl,
            ],
            'appLogoUrl' => $appLogoUrl,
            'heroImages' => [
                'main' => $heroMainUrl,
                'devices' => $heroDevicesUrl,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
            'storefrontTheme' => $storefrontTheme,
            'storefrontIsPublic' => $request->attributes->get('storefront_shop') !== null,
            'storefrontPublicBaseUrl' => $request->attributes->get('storefront_shop') ? $request->root() : null,
        ];
    }

    /**
     * Retourne les dépôts et le dépôt courant pour une requête (pour les vues Inertia).
     * Utilisable par les contrôleurs Hardware pour s'assurer que la navbar affiche le sélecteur.
     *
     * @return array{depots: array, currentDepot: array|null}
     */
    public static function getDepotsForRequest(Request $request): array
    {
        $user = $request->user();
        $currentDepot = null;
        $depots = [];
        if (!$user || !$user->tenant_id || !\Illuminate\Support\Facades\Schema::hasTable('depots')) {
            return ['depots' => [], 'currentDepot' => null];
        }
        try {
            $depotsQuery = \App\Models\Depot::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->orderBy('name');
            if (method_exists($user, 'isRoot') && !$user->isRoot() && method_exists($user, 'depots')) {
                // Qualifier la colonne pour éviter l'ambiguïté avec la table pivot user_depot
                $userDepotIds = $user->depots()->pluck('depots.id')->map(fn ($id) => (int) $id)->toArray();
                if (!empty($userDepotIds)) {
                    $depotsQuery->whereIn('id', $userDepotIds);
                }
            }
            $depots = $depotsQuery->get(['id', 'name', 'code'])
                ->map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name, 'code' => $d->code ?? ''])
                ->values()
                ->toArray();
            $depotId = $request->session()->get('current_depot_id');
            $depotId = $depotId !== null ? (int) $depotId : null;
            if ($depotId && count($depots) > 0) {
                $depot = collect($depots)->firstWhere('id', $depotId);
                if ($depot) {
                    $currentDepot = $depot;
                }
            }
            if (!$currentDepot && count($depots) === 1) {
                $currentDepot = $depots[0];
            }
        } catch (\Throwable $e) {
            Log::debug('getDepotsForRequest failed', ['error' => $e->getMessage()]);
        }
        return ['depots' => $depots, 'currentDepot' => $currentDepot];
    }

    /**
     * Set the root template that's loaded on the first Inertia page visit.
     */
    public function rootView(Request $request): string
    {
        return parent::rootView($request);
    }
}
