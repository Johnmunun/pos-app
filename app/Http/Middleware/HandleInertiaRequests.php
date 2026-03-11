<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;

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

        // Thème storefront (couleurs primaire/secondaire) — uniquement sur les routes storefront
        // Injecté dans le HTML initial pour éviter le flash de couleur au refresh
        $storefrontTheme = null;
        if (str_starts_with($request->path(), 'ecommerce/storefront')) {
            $shopId = $user ? ($user->shop_id ?? $user->tenant_id) : null;
            if ($shopId) {
                $shop = \App\Models\Shop::find($shopId);
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
            }
            if (!$storefrontTheme) {
                $storefrontTheme = ['primary' => '#f59e0b', 'secondary' => '#d97706'];
            }
        }

        // Logo global de l'application (OmniPOS) - même pour les invités
        try {
            $fullPath = 'settings/app/app-logo.png';
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($fullPath)) {
                $appLogoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($fullPath);
            }
        } catch (\Throwable $e) {
            Log::debug('Error getting app logo for Inertia', ['error' => $e->getMessage()]);
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
                'receipt_auto_print' => $receiptAutoPrint,
                'logo_url' => $shopLogoUrl,
            ],
            'appLogoUrl' => $appLogoUrl,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
            'storefrontTheme' => $storefrontTheme,
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
