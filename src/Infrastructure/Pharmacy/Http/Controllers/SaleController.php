<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\Pharmacy\UseCases\Sales\CreateDraftSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\UpdateSaleLinesUseCase;
use Src\Application\Pharmacy\UseCases\Sales\FinalizeSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\CancelSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\AttachCustomerToSaleUseCase;
use Src\Application\Finance\UseCases\Invoice\CreateInvoiceFromSaleUseCase;
use Src\Application\Finance\UseCases\Debt\CreateDebtUseCase;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Application\Pharmacy\DTO\SaleLineDTO;
use Src\Infrastructure\Pharmacy\Models\ProductModel as PharmacyProductModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use App\Models\CashRegister;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Shop;
use App\Services\CurrencyConversionService;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;
use Src\Application\Quincaillerie\Services\DepotFilterService;
use Src\Infrastructure\Pharmacy\Adapters\QuincaillerieProductRepositoryAdapter;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface as QuincaillerieProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Infrastructure\Pharmacy\Adapters\HardwareUpdateStockUseCase;
use Src\Shared\ValueObjects\Money;
use Src\Domain\Pharmacy\Entities\Sale as SaleEntity;
use Src\Application\Referral\Services\ReferralService;

class SaleController
{
    private function getModule(): string
    {
        // Vérifier d'abord l'URL pour être sûr
        $url = request()->url();
        $path = request()->path();
        
        // Si l'URL contient /hardware/, c'est le module Hardware
        if (str_contains($path, 'hardware/') || str_contains($url, '/hardware/')) {
            return 'Hardware';
        }
        
        // Sinon, vérifier le préfixe de la route
        $prefix = request()->route()?->getPrefix();
        $normalizedPrefix = $prefix ? trim($prefix, '/') : '';
        
        return $normalizedPrefix === 'hardware' ? 'Hardware' : 'Pharmacy';
    }

    public function __construct(
        private CreateDraftSaleUseCase $createDraftSaleUseCase,
        private UpdateSaleLinesUseCase $updateSaleLinesUseCase,
        private FinalizeSaleUseCase $finalizeSaleUseCase,
        private CancelSaleUseCase $cancelSaleUseCase,
        private AttachCustomerToSaleUseCase $attachCustomerToSaleUseCase,
        private SaleRepositoryInterface $saleRepository,
        private SaleLineRepositoryInterface $saleLineRepository,
        private CurrencyConversionService $currencyConversion,
        private GetStoreSettingsUseCase $getStoreSettingsUseCase,
        private StoreLogoService $storeLogoService,
        private CreateInvoiceFromSaleUseCase $createInvoiceFromSaleUseCase,
        private CreateDebtUseCase $createDebtUseCase,
        private ?DepotFilterService $depotFilterService = null,
        private ?QuincaillerieProductRepositoryInterface $hardwareProductRepository = null,
        private ?ProductRepositoryInterface $pharmacyProductRepository = null,
        private ?ReferralService $referralService = null
    ) {}

    /**
     * Retourne le ProductRepository adapté au module courant.
     * - Pharmacy : repository pharmacie
     * - Hardware : adapter vers Quincaillerie
     */
    private function getProductRepository(): ProductRepositoryInterface
    {
        if ($this->getModule() === 'Hardware' && $this->hardwareProductRepository !== null) {
            return new QuincaillerieProductRepositoryAdapter($this->hardwareProductRepository);
        }

        if ($this->pharmacyProductRepository !== null) {
            return $this->pharmacyProductRepository;
        }

        // Fallback : résolution via le conteneur
        return app(ProductRepositoryInterface::class);
    }

    private function getEffectiveShopCurrency(string $shopId, $authUser): string
    {
        $shop = Shop::find($shopId);
        $tenantId = $authUser !== null ? ($authUser->tenant_id ?? $shopId) : $shopId;
        $defaultCurrency = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
        if ($defaultCurrency) {
            return $defaultCurrency->code;
        }

        return $shop !== null ? ($shop->currency ?? 'CDF') : 'CDF';
    }

    private function getShopId(Request $request): string
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
        $userModel = UserModel::find($user->id);
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
        $authUser = $request->user();
        $userModel = $authUser ? UserModel::find($authUser->id) : null;
        $canViewAllSales = $userModel && ($userModel->isRoot() || $userModel->hasPermission('pharmacy.sales.view.all'));

        $from = $request->filled('from') ? new \DateTimeImmutable($request->input('from')) : null;
        $to = $request->filled('to') ? new \DateTimeImmutable($request->input('to')) : null;
        $status = $request->input('status');
        $saleType = $request->input('sale_type');

        $sales = $this->saleRepository->findByShop($shopId, $from, $to);

        // Filtrer par dépôt :
        // - Hardware : logique avancée avec permissions (déjà en place)
        // - Pharmacy : si un dépôt est sélectionné, ne voir que ce dépôt + dépôt central
        $module = $this->getModule();
        if ($module === 'Hardware' && $this->depotFilterService) {
            $currentDepotId = $request->session()->get('current_depot_id');
            $user = $request->user();
            $permissions = $user ? $user->permissionCodes() : [];
            $canViewAll = in_array('hardware.warehouse.view_all', $permissions, true) || in_array('*', $permissions, true);

            // Optimisation : récupérer tous les depot_id en une seule requête
            $saleIds = array_map(fn ($sale) => $sale->getId(), $sales);
            $saleDepotMap = [];
            if (!empty($saleIds)) {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \Src\Infrastructure\Pharmacy\Models\SaleModel> $saleModels */
                $saleModels = \Src\Infrastructure\Pharmacy\Models\SaleModel::whereIn('id', $saleIds)
                    ->get(['id', 'depot_id'])
                    ->keyBy('id');
                foreach ($saleModels as $model) {
                    /** @var \Src\Infrastructure\Pharmacy\Models\SaleModel $model */
                    $saleDepotMap[$model->id] = $model->depot_id;
                }
            }

            if (!$canViewAll) {
                $userDepotIds = $this->depotFilterService->getUserDepotIds($user);
                $sales = array_values(array_filter($sales, function ($sale) use ($currentDepotId, $userDepotIds, $saleDepotMap) {
                    $saleDepotId = $saleDepotMap[$sale->getId()] ?? null;

                    // Dépôt central (null) toujours visible
                    if ($saleDepotId === null) {
                        return true;
                    }

                    // Si un dépôt est sélectionné, voir uniquement ce dépôt + dépôt central
                    if ($currentDepotId) {
                        return (int) $saleDepotId === (int) $currentDepotId;
                    }

                    // Voir tous les dépôts assignés + dépôt central
                    return in_array((int) $saleDepotId, $userDepotIds, true);
                }));
            } elseif ($currentDepotId) {
                // Si view_all_warehouse et dépôt sélectionné, filtrer par ce dépôt + dépôt central
                $sales = array_values(array_filter($sales, function ($sale) use ($currentDepotId, $saleDepotMap) {
                    $saleDepotId = $saleDepotMap[$sale->getId()] ?? null;
                    return $saleDepotId === null || (int) $saleDepotId === (int) $currentDepotId;
                }));
            }
        } elseif ($module === 'Pharmacy') {
            $currentDepotId = $request->session()->get('current_depot_id');
            if ($currentDepotId) {
                // Récupérer la map vente -> depot_id une seule fois
                $saleIds = array_map(fn ($sale) => $sale->getId(), $sales);
                $saleDepotMap = [];
                if (!empty($saleIds)) {
                    /** @var \Illuminate\Database\Eloquent\Collection<int, \Src\Infrastructure\Pharmacy\Models\SaleModel> $saleModels */
                    $saleModels = \Src\Infrastructure\Pharmacy\Models\SaleModel::whereIn('id', $saleIds)
                        ->get(['id', 'depot_id'])
                        ->keyBy('id');
                    foreach ($saleModels as $model) {
                        /** @var \Src\Infrastructure\Pharmacy\Models\SaleModel $model */
                        $saleDepotMap[$model->id] = $model->depot_id;
                    }
                }

                // Pour Pharmacy : si un dépôt est sélectionné, ne voir que ce dépôt + dépôt central
                $sales = array_values(array_filter($sales, function ($sale) use ($currentDepotId, $saleDepotMap) {
                    $saleDepotId = $saleDepotMap[$sale->getId()] ?? null;
                    return $saleDepotId === null || (int) $saleDepotId === (int) $currentDepotId;
                }));
            }
        }
        
        if (!$canViewAllSales && $authUser) {
            $sales = array_values(array_filter($sales, fn ($s) => (int) $s->getCreatedBy() === (int) $authUser->id));
        }
        if ($status && in_array($status, ['DRAFT', 'COMPLETED', 'CANCELLED'], true)) {
            $sales = array_values(array_filter($sales, fn ($s) => $s->getStatus() === $status));
        }
        if ($saleType && in_array($saleType, ['retail', 'wholesale'], true)) {
            $sales = array_values(array_filter($sales, fn ($s) => $s->getSaleType() === $saleType));
        }

        $creatorIds = array_unique(array_map(fn ($s) => $s->getCreatedBy(), $sales));
        $creators = $creatorIds !== [] ? UserModel::whereIn('id', $creatorIds)->get()->keyBy('id') : collect();

        $customerIds = array_unique(array_filter(array_map(fn ($s) => $s->getCustomerId(), $sales)));
        $customers = [];
        if ($customerIds !== []) {
            $customerModels = \App\Models\Customer::whereIn('id', $customerIds)->get(['id', 'full_name']);
            foreach ($customerModels as $c) {
                $full = $c->full_name ?? '';
                $short = strlen($full) > 25 ? mb_substr($full, 0, 22) . '…' : $full;
                $customers[(string) $c->id] = $short ?: '—';
            }
        }

        $salesData = array_map(function ($sale) use ($creators, $customers) {
            $creator = $creators->get($sale->getCreatedBy());
            $customerId = $sale->getCustomerId();
            $customerShortName = $customerId && isset($customers[(string) $customerId]) ? $customers[(string) $customerId] : null;
            return [
                'id' => $sale->getId(),
                'status' => $sale->getStatus(),
                'sale_type' => $sale->getSaleType(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'paid_amount' => $sale->getPaidAmount()->getAmount(),
                'balance_amount' => $sale->getBalance()->getAmount(),
                'currency' => $sale->getCurrency(),
                'customer_id' => $customerId,
                'customer_short_name' => $customerShortName,
                'created_at' => $sale->getCreatedAt()->format('Y-m-d H:i'),
                'seller_name' => $creator ? $creator->name : '—',
            ];
        }, $sales);

        return Inertia::render($this->getModule() . '/Sales/Index', [
            'sales' => $salesData,
            'filters' => $request->only(['from', 'to', 'status', 'sale_type']),
            'canViewAllSales' => $canViewAllSales,
            'routePrefix' => $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy',
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $authUser = $request->user();
        $shopCurrency = $this->getEffectiveShopCurrency($shopId, $authUser);

        $module = $this->getModule();
        $isHardware = $module === 'Hardware';

        // Products with images - conversion vers devise boutique si nécessaire
        $tenantId = $authUser !== null ? ($authUser->tenant_id ?? $shopId) : $shopId;

        if ($isHardware) {
            // Module Hardware : utiliser les produits Quincaillerie
            $imageService = app(\Src\Infrastructure\Quincaillerie\Services\ProductImageService::class);
            $productsQuery = \Src\Infrastructure\Quincaillerie\Models\ProductModel::where('shop_id', $shopId)
                ->where('is_active', true);

            // Filtre dépôt pour Hardware
            if ($this->depotFilterService) {
                $productsQuery = $this->depotFilterService->applyDepotFilter($productsQuery, $request, 'depot_id');
            }

            /** @var \Illuminate\Database\Eloquent\Collection<int, \Src\Infrastructure\Quincaillerie\Models\ProductModel> $productsCollection */
            $productsCollection = $productsQuery->orderBy('name')->get();
            $products = $productsCollection->map(function ($p) use ($shopCurrency, $tenantId, $imageService) {
                /** @var \Src\Infrastructure\Quincaillerie\Models\ProductModel $p */
                $productCurrency = $p->price_currency ?? $shopCurrency;
                $priceAmount = (float) ($p->price_amount ?? 0);
                $wholesaleAmount = $p->price_wholesale_normal !== null ? (float) $p->price_wholesale_normal : null;

                if ($productCurrency !== $shopCurrency) {
                    $priceAmount = $this->currencyConversion->convertOrKeep($priceAmount, $productCurrency, $shopCurrency, $tenantId);
                    if ($wholesaleAmount !== null) {
                        $wholesaleAmount = $this->currencyConversion->convertOrKeep($wholesaleAmount, $productCurrency, $shopCurrency, $tenantId);
                    }
                }

                // Prix minimum (non négociable)
                $minPriceRetail = $p->price_non_negotiable !== null ? (float) $p->price_non_negotiable : null;
                $minPriceWholesale = $p->price_non_negotiable_wholesale !== null ? (float) $p->price_non_negotiable_wholesale : null;
                
                // Conversion des prix minimum en devise boutique si nécessaire
                if ($minPriceRetail !== null && $productCurrency !== $shopCurrency) {
                    $minPriceRetail = $this->currencyConversion->convertOrKeep($minPriceRetail, $productCurrency, $shopCurrency, $tenantId);
                }
                if ($minPriceWholesale !== null && $productCurrency !== $shopCurrency) {
                    $minPriceWholesale = $this->currencyConversion->convertOrKeep($minPriceWholesale, $productCurrency, $shopCurrency, $tenantId);
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'barcode' => $p->barcode ?? null,
                    'price_amount' => $priceAmount,
                    'wholesale_price_amount' => $wholesaleAmount,
                    'wholesale_min_quantity' => null,
                    'price_currency' => $shopCurrency,
                    'price_non_negotiable' => $minPriceRetail,
                    'price_non_negotiable_wholesale' => $minPriceWholesale,
                    'stock' => (float) ($p->stock ?? 0),
                    'category_id' => $p->category_id,
                    'image_url' => $p->image_path
                        ? $imageService->getUrl($p->image_path, $p->image_type ?: 'upload')
                        : null,
                    'type_unite' => $p->type_unite ?? 'UNITE',
                    'quantite_par_unite' => (int) ($p->quantite_par_unite ?? 1),
                    'est_divisible' => (bool) ($p->est_divisible ?? true),
                ];
            })->toArray();

            $canUseWholesale = $authUser !== null
                && ($authUser->isRoot()
                    || $authUser->hasPermission('hardware.sales.wholesale')
                    || $authUser->hasPermission('pharmacy.sales.wholesale'));

            // Categories Hardware (Quincaillerie)
            $categories = \Src\Infrastructure\Quincaillerie\Models\CategoryModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->toArray();
        } else {
            // Module Pharmacy existant
            $imageService = app(\Src\Infrastructure\Pharmacy\Services\ProductImageService::class);
            $productsQuery = PharmacyProductModel::where('shop_id', $shopId)
                ->where('is_active', true);

            /** @var \Illuminate\Database\Eloquent\Collection<int, \Src\Infrastructure\Pharmacy\Models\ProductModel> $productsCollection */
            $productsCollection = $productsQuery->orderBy('name')->get();
            $products = $productsCollection->map(function ($p) use ($shopCurrency, $tenantId, $imageService) {
                /** @var \Src\Infrastructure\Pharmacy\Models\ProductModel $p */
                $productCurrency = $p->price_currency ?? $shopCurrency;
                $priceAmount = (float) ($p->price_amount ?? 0);
                $wholesaleAmount = $p->wholesale_price_amount !== null ? (float) $p->wholesale_price_amount : null;

                if ($productCurrency !== $shopCurrency) {
                    $priceAmount = $this->currencyConversion->convertOrKeep($priceAmount, $productCurrency, $shopCurrency, $tenantId);
                    if ($wholesaleAmount !== null) {
                        $wholesaleAmount = $this->currencyConversion->convertOrKeep($wholesaleAmount, $productCurrency, $shopCurrency, $tenantId);
                    }
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'barcode' => $p->barcode ?? null,
                    'price_amount' => $priceAmount,
                    'wholesale_price_amount' => $wholesaleAmount,
                    'wholesale_min_quantity' => $p->wholesale_min_quantity !== null ? (int) $p->wholesale_min_quantity : null,
                    'price_currency' => $shopCurrency,
                    'stock' => (float) ($p->stock ?? 0),
                    'category_id' => $p->category_id,
                    'image_url' => $imageService->getUrlFromPath($p->image_path, $p->image_type ?? 'upload'),
                    'type_unite' => $p->type_unite ?? 'UNITE',
                    'quantite_par_unite' => (int) ($p->quantite_par_unite ?? 1),
                    'est_divisible' => (bool) ($p->est_divisible ?? true),
                ];
            })->toArray();

            $canUseWholesale = $authUser !== null
                && ($authUser->isRoot() || $authUser->hasPermission('pharmacy.sales.wholesale'));

            // Categories Pharmacy
            $categories = \Src\Infrastructure\Pharmacy\Models\CategoryModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->toArray();
        }

        $customers = \App\Models\Customer::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->map(fn ($c) => [
                'id' => (string) $c->id,
                'full_name' => $c->full_name,
                'phone' => $c->phone,
                'email' => $c->email,
            ])->toArray();

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\CashRegister> $cashRegistersCollection */
        $cashRegistersCollection = CashRegister::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $cashRegisters = $cashRegistersCollection->map(function ($reg) {
            /** @var \App\Models\CashRegister $reg */
            /** @var \App\Models\CashRegisterSession|null $openSession */
            $openSession = $reg->sessions()->where('status', 'open')->first();
            return [
                'id' => $reg->id,
                'name' => $reg->name,
                'code' => $reg->code,
                'open_session' => $openSession ? [
                    'id' => $openSession->id,
                    'opening_balance' => (float) $openSession->opening_balance,
                ] : null,
            ];
        })->toArray();

        // Devises et taux pour la conversion (dollar ↔ franc, etc.)
        $currenciesList = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
        $defaultCurrencyModel = $currenciesList->firstWhere('is_default', true) ?? $currenciesList->first();
        $defaultCode = $defaultCurrencyModel ? strtoupper($defaultCurrencyModel->code) : strtoupper($shopCurrency);
        $currenciesForPos = $currenciesList->map(fn ($c) => [
            'code' => strtoupper($c->code),
            'name' => $c->name,
            'symbol' => $c->symbol ?? $c->code,
        ])->toArray();
        $exchangeRatesMap = [$defaultCode => 1.0];
        if ($defaultCurrencyModel) {
            foreach ($currenciesList as $c) {
                $code = strtoupper($c->code);
                if ($code === $defaultCode) {
                    continue;
                }
                $fromDefault = ExchangeRate::where('tenant_id', $tenantId)
                    ->where('from_currency_id', $defaultCurrencyModel->id)
                    ->where('to_currency_id', $c->id)
                    ->orderByDesc('effective_date')
                    ->first();
                if ($fromDefault && (float) $fromDefault->rate > 0) {
                    $exchangeRatesMap[$code] = (float) $fromDefault->rate;
                } else {
                    $toDefault = ExchangeRate::where('tenant_id', $tenantId)
                        ->where('from_currency_id', $c->id)
                        ->where('to_currency_id', $defaultCurrencyModel->id)
                        ->orderByDesc('effective_date')
                        ->first();
                    if ($toDefault && (float) $toDefault->rate > 0) {
                        $exchangeRatesMap[$code] = 1.0 / (float) $toDefault->rate;
                    } else {
                        $exchangeRatesMap[$code] = 1.0;
                    }
                }
            }
        }

        return Inertia::render($this->getModule() . '/Sales/Create', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'canUseWholesale' => $canUseWholesale,
            'cashRegisters' => $cashRegisters,
            'currency' => $defaultCode,
            'currencies' => $currenciesForPos,
            'exchangeRates' => $exchangeRatesMap,
            'routePrefix' => $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|string',
            'currency' => 'required|string|size:3',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'cash_register_id' => 'nullable|integer',
            'cash_register_session_id' => 'nullable|integer',
            'sale_mode' => 'nullable|string|in:retail,wholesale',
        ]);

        $shopId = $this->getShopId($request);
        $authUser = $request->user();
        if ($authUser === null) {
            abort(403, 'User not authenticated.');
        }
        $userId = (int) $authUser->id;
        $customerId = $request->input('customer_id') ?: null;
        $shopCurrency = $this->getEffectiveShopCurrency($shopId, $authUser);
        $currency = $request->input('currency') ?? $shopCurrency;
        $linesInput = $request->input('lines', []);

        $cashRegisterId = $request->filled('cash_register_id') ? (int) $request->input('cash_register_id') : null;
        $cashRegisterSessionId = $request->filled('cash_register_session_id') ? (int) $request->input('cash_register_session_id') : null;
        if ($cashRegisterSessionId !== null) {
            $session = \App\Models\CashRegisterSession::where('id', $cashRegisterSessionId)
                ->where('status', 'open')
                ->whereHas('cashRegister', fn ($q) => $q->where('shop_id', $shopId))
                ->first();
            if (!$session) {
                return response()->json(['message' => 'Session de caisse invalide ou fermée.'], 422);
            }
            $cashRegisterId = $cashRegisterId ?? $session->cash_register_id;
        }

        $canUseWholesale = $authUser->isRoot() || $authUser->hasPermission('pharmacy.sales.wholesale');
        $saleMode = $request->input('sale_mode', 'retail');
        if ($saleMode === 'wholesale' && !$canUseWholesale) {
            $saleMode = 'retail';
        }
        $saleType = $saleMode === 'wholesale' ? \Src\Domain\Pharmacy\Entities\Sale::SALE_TYPE_WHOLESALE : \Src\Domain\Pharmacy\Entities\Sale::SALE_TYPE_RETAIL;

        $sale = $this->createDraftSaleUseCase->execute($shopId, $customerId, $currency, $userId, $cashRegisterId, $cashRegisterSessionId, $saleType);

        // Pour Hardware : valider que tous les produits sont accessibles depuis le dépôt actuel
        $isHardware = $this->getModule() === 'Hardware';
        if ($isHardware && $this->depotFilterService) {
            $currentDepotId = $request->session()->get('current_depot_id');
            $user = $request->user();
            $permissions = $user ? $user->permissionCodes() : [];
            $canViewAll = in_array('hardware.warehouse.view_all', $permissions, true) || in_array('*', $permissions, true);
            
            foreach ($linesInput as $row) {
                $productId = $row['product_id'];
                
                // Récupérer le produit directement pour vérifier son depot_id
                $productModel = \Src\Infrastructure\Quincaillerie\Models\ProductModel::where('id', $productId)
                    ->where('shop_id', $shopId)
                    ->where('is_active', true)
                    ->first();
                
                if (!$productModel) {
                    $productName = 'Inconnu';
                    try {
                        $tempProduct = \Src\Infrastructure\Quincaillerie\Models\ProductModel::find($productId);
                        $productName = $tempProduct?->name ?? 'Inconnu';
                    } catch (\Throwable $e) {
                        // Ignorer
                    }
                    return response()->json([
                        'message' => sprintf(
                            'Le produit "%s" (ID: %s) n\'existe pas, a été supprimé ou est désactivé.',
                            $productName,
                            $productId
                        )
                    ], 422);
                }
                
                // Vérifier l'accès au dépôt
                $productDepotId = $productModel->depot_id;
                
                // Log pour diagnostic
                \Illuminate\Support\Facades\Log::info('Hardware sale validation - Product depot check', [
                    'product_id' => $productId,
                    'product_name' => $productModel->name,
                    'product_depot_id' => $productDepotId,
                    'current_depot_id' => $currentDepotId,
                    'can_view_all' => $canViewAll,
                    'shop_id' => $shopId,
                ]);
                
                // Si le produit est dans le dépôt central (depot_id = null), il est accessible par tous
                if ($productDepotId === null) {
                    continue; // Produit accessible
                }
                
                // Si l'utilisateur peut voir tous les dépôts, il peut accéder à tous les produits
                if ($canViewAll) {
                    // Si un dépôt est sélectionné, vérifier que le produit est dans ce dépôt ou dépôt central
                    if ($currentDepotId) {
                        if ((int) $productDepotId === (int) $currentDepotId) {
                            continue; // Produit accessible
                        }
                    } else {
                        continue; // Pas de dépôt sélectionné, accessible
                    }
                } else {
                    // Vérifier que l'utilisateur a accès au dépôt du produit
                    $userDepotIds = $this->depotFilterService->getUserDepotIds($user);
                    
                    // Si un dépôt est sélectionné, vérifier qu'il correspond au dépôt du produit
                    if ($currentDepotId) {
                        if ((int) $productDepotId === (int) $currentDepotId && 
                            (empty($userDepotIds) || in_array((int) $currentDepotId, $userDepotIds, true))) {
                            continue; // Produit accessible
                        }
                    } else {
                        // Pas de dépôt sélectionné : vérifier que l'utilisateur a accès au dépôt du produit
                        if (empty($userDepotIds) || in_array((int) $productDepotId, $userDepotIds, true)) {
                            continue; // Produit accessible
                        }
                    }
                }
                
                // Produit non accessible
                return response()->json([
                    'message' => sprintf(
                        'Le produit "%s" (ID: %s) n\'est pas accessible depuis le dépôt actuellement sélectionné (dépôt ID: %s, produit dans dépôt ID: %s). Veuillez changer de dépôt ou retirer ce produit du panier.',
                        $productModel->name,
                        $productId,
                        $currentDepotId ?? 'aucun',
                        $productDepotId ?? 'central'
                    )
                ], 422);
            }
        }

        $lines = [];
        foreach ($linesInput as $row) {
            $lines[] = new SaleLineDTO(
                $row['product_id'],
                (float) $row['quantity'],
                (float) $row['unit_price'],
                isset($row['discount_percent']) ? (float) $row['discount_percent'] : null
            );
        }

        if (count($lines) > 0) {
            try {
                $this->updateSaleLinesUseCase->execute($sale->getId(), $lines);
                // Recharger la vente pour récupérer les totaux mis à jour
                $sale = $this->saleRepository->findById($sale->getId()) ?? $sale;
            } catch (\InvalidArgumentException $e) {
                $errorMessage = $e->getMessage();
                // Améliorer le message d'erreur pour Hardware
                if ($isHardware && str_contains($errorMessage, 'Product not found')) {
                    $currentDepotId = $request->session()->get('current_depot_id');
                    if ($currentDepotId) {
                        $errorMessage .= sprintf(
                            ' Le produit n\'est peut-être pas accessible depuis le dépôt actuellement sélectionné (dépôt ID: %s).',
                            $currentDepotId
                        );
                    }
                }
                return response()->json(['message' => $errorMessage], 422);
            }
        }

        if ($customerId) {
            $this->attachCustomerToSaleUseCase->execute($sale->getId(), $customerId);
            $sale = $this->saleRepository->findById($sale->getId()) ?? $sale;
        }

        return response()->json([
            'message' => 'Draft sale created',
            'sale' => [
                'id' => $sale->getId(),
                'status' => $sale->getStatus(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'currency' => $sale->getCurrency(),
            ],
        ], 201);
    }

    public function quickCreateCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);
        $shopId = $this->getShopId($request);
        $authUser = $request->user();
        $tenantId = $authUser && $authUser->tenant_id ? $authUser->tenant_id : $shopId;
        $name = trim($request->input('name'));
        $phone = $request->filled('phone') ? trim($request->input('phone')) : null;
        $email = $request->filled('email') ? trim($request->input('email')) : null;

        try {
            $code = 'C' . now()->format('YmdHis') . substr(uniqid(), -4);
            $customer = \App\Models\Customer::create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'full_name' => $name,
                'phone' => $phone,
                'email' => $email,
                'is_active' => true,
            ]);
            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => (string) $customer->id,
                    'full_name' => $customer->full_name,
                    'phone' => $customer->phone ?? '',
                    'email' => $customer->email ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Quick create customer failed', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Erreur lors de la création du client.'], 500);
        }
    }

    public function show(Request $request, string $id): Response|JsonResponse
    {
        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            abort(404);
        }
        $authUser = $request->user();
        $userModel = $authUser ? UserModel::find($authUser->id) : null;
        $canViewAllSales = $userModel && ($userModel->isRoot() || $userModel->hasPermission('pharmacy.sales.view.all'));
        if (!$canViewAllSales && $authUser && (int) $sale->getCreatedBy() !== (int) $authUser->id) {
            abort(403, 'Vous ne pouvez consulter que vos propres ventes.');
        }

        $lines = $this->saleLineRepository->findBySale($id);
        $linesData = [];
        $isHardware = $this->getModule() === 'Hardware';
        foreach ($lines as $line) {
            $product = $isHardware
                ? \Src\Infrastructure\Quincaillerie\Models\ProductModel::find($line->getProductId())
                : PharmacyProductModel::find($line->getProductId());
            $linesData[] = [
                'id' => $line->getId(),
                'product_id' => $line->getProductId(),
                'product_name' => $product ? $product->name : '',
                'quantity' => $line->getQuantity()->getValue(),
                'unit_price' => $line->getUnitPrice()->getAmount(),
                'line_total' => $line->getLineTotal()->getAmount(),
                'currency' => $line->getUnitPrice()->getCurrency(),
            ];
        }

        $customer = null;
        if ($sale->getCustomerId()) {
            $c = \App\Models\Customer::find($sale->getCustomerId());
            if ($c) {
                $customer = ['id' => (string) $c->id, 'full_name' => $c->full_name, 'phone' => $c->phone];
            }
        }

        $seller = UserModel::find($sale->getCreatedBy());

        return Inertia::render($this->getModule() . '/Sales/Show', [
            'routePrefix' => $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy',
            'sale' => [
                'id' => $sale->getId(),
                'status' => $sale->getStatus(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'paid_amount' => $sale->getPaidAmount()->getAmount(),
                'balance_amount' => $sale->getBalance()->getAmount(),
                'currency' => $sale->getCurrency(),
                'created_at' => $sale->getCreatedAt()->format('Y-m-d H:i'),
                'completed_at' => $sale->getCompletedAt() ? $sale->getCompletedAt()->format('Y-m-d H:i') : null,
                'seller_name' => $seller ? $seller->name : '—',
            ],
            'lines' => $linesData,
            'customer' => $customer,
        ]);
    }

    public function receipt(Request $request, string $id)
    {
        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            abort(404);
        }
        $authUser = $request->user();
        $userModel = $authUser ? UserModel::find($authUser->id) : null;
        $canViewAllSales = $userModel && ($userModel->isRoot() || $userModel->hasPermission('pharmacy.sales.view.all'));
        if (!$canViewAllSales && $authUser && (int) $sale->getCreatedBy() !== (int) $authUser->id) {
            abort(403, 'Vous ne pouvez consulter que vos propres ventes.');
        }

        $lines = $this->saleLineRepository->findBySale($id);
        $isHardware = $this->getModule() === 'Hardware';
        $shop = Shop::find($shopId);
        $displayCurrency = $this->getEffectiveShopCurrency($shopId, $authUser) ?: 'CDF';

        $linesData = [];
        foreach ($lines as $line) {
            $product = $isHardware
                ? \Src\Infrastructure\Quincaillerie\Models\ProductModel::find($line->getProductId())
                : PharmacyProductModel::find($line->getProductId());
            $linesData[] = [
                'product_name' => $product ? $product->name : '—',
                'quantity' => $line->getQuantity()->getValue(),
                'unit_price' => (float) $line->getUnitPrice()->getAmount(),
                'line_total' => (float) $line->getLineTotal()->getAmount(),
                'currency' => $displayCurrency,
            ];
        }

        $seller = UserModel::find($sale->getCreatedBy());
        $customer = null;
        if ($sale->getCustomerId()) {
            $c = \App\Models\Customer::find($sale->getCustomerId());
            if ($c) {
                $customer = $c->full_name;
            }
        }

        $settings = $this->getStoreSettingsUseCase->execute($shopId);
        $logoUrl = $settings && $settings->getLogoPath()
            ? $this->storeLogoService->getUrl($settings->getLogoPath())
            : null;

        return view('pharmacy.receipt', [
            'shop_name' => $shop ? $shop->name : 'Boutique',
            'logo_url' => $logoUrl,
            'sale' => [
                'id' => $sale->getId(),
                'created_at' => $sale->getCreatedAt()->format('d/m/Y H:i'),
                'total_amount' => (float) $sale->getTotal()->getAmount(),
                'paid_amount' => (float) $sale->getPaidAmount()->getAmount(),
                'balance_amount' => (float) $sale->getBalance()->getAmount(),
                'currency' => $displayCurrency,
                'seller_name' => $seller ? $seller->name : '—',
            ],
            'lines' => $linesData,
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|string',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId || $sale->getStatus() !== \Src\Domain\Pharmacy\Entities\Sale::STATUS_DRAFT) {
            return response()->json(['message' => 'Sale not found or not editable'], 404);
        }

        $lines = [];
        foreach ($request->input('lines', []) as $row) {
            $lines[] = new SaleLineDTO(
                $row['product_id'],
                (float) $row['quantity'],
                (float) $row['unit_price'],
                isset($row['discount_percent']) ? (float) $row['discount_percent'] : null
            );
        }
        $this->updateSaleLinesUseCase->execute($id, $lines);

        if ($request->has('customer_id')) {
            $cid = $request->input('customer_id');
            if ($cid) {
                $this->attachCustomerToSaleUseCase->execute($id, $cid);
            }
        }

        $sale = $this->saleRepository->findById($id);
        return response()->json([
            'message' => 'Sale updated',
            'sale' => [
                'id' => $sale->getId(),
                'total_amount' => $sale->getTotal()->getAmount(),
            ],
        ]);
    }

    public function finalize(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        try {
            $authUser = $request->user();
            if ($authUser === null) {
                abort(403, 'User not authenticated.');
            }
            // Si module Hardware, utiliser un flux de finalisation adapté (sans lots Pharmacy)
            $isHardware = $this->getModule() === 'Hardware';
            if ($isHardware) {
                $this->finalizeHardwareSale($sale, (float) $request->input('paid_amount'), (int) $authUser->id);
            } else {
                $this->finalizeSaleUseCase->execute($id, (float) $request->input('paid_amount'), (int) $authUser->id);
            }

            // Liaison Caisse → Finance : facture + dette client si paiement partiel
            $sale = $this->saleRepository->findById($id);
            if ($sale && $sale->getStatus() === \Src\Domain\Pharmacy\Entities\Sale::STATUS_COMPLETED) {
                $tenantId = (string) ($authUser->tenant_id ?? $shopId);
                $this->createInvoiceFromSaleUseCase->execute(
                    $tenantId,
                    $shopId,
                    $id,
                    $sale->getTotal(),
                    $sale->getPaidAmount(),
                    \Src\Domain\Finance\Entities\Invoice::STATUS_VALIDATED
                );
                if ($sale->getBalance()->getAmount() > 0 && $sale->getCustomerId() !== null) {
                    $this->createDebtUseCase->execute(
                        $tenantId,
                        $shopId,
                        'client',
                        $sale->getCustomerId(),
                        $sale->getTotal(),
                        $sale->getPaidAmount(),
                        'sale',
                        $id,
                        null
                    );
                }

                // Enregistrer la transaction pour le système de parrainage
                if ($this->referralService !== null) {
                    $sourceType = $isHardware ? 'hardware_sale' : 'pharmacy_sale';
                    $buyerUserId = $sale->getCreatedBy();
                    if ($buyerUserId) {
                        $this->referralService->recordTransaction(
                            (int) $sale->getShopId(),
                            (int) $buyerUserId,
                            $sale->getTotal()->getAmount(),
                            $sourceType,
                            $sale->getId()
                        );
                    }
                }
            }

            return response()->json(['message' => 'Sale finalized successfully']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Finalisation d'une vente pour le module Hardware.
     * Reprend la logique métier de FinalizeSaleUseCase mais avec HardwareUpdateStockUseCase
     * qui ne dépend pas des lots Pharmacy (batches).
     */
    private function finalizeHardwareSale(SaleEntity $sale, float $paidAmount, int $userId): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($sale, $paidAmount, $userId): void {
            if ($sale->getStatus() !== SaleEntity::STATUS_DRAFT) {
                \Illuminate\Support\Facades\Log::warning('Hardware sale finalization failed: sale is not in DRAFT status', [
                    'sale_id' => $sale->getId(),
                    'current_status' => $sale->getStatus(),
                ]);
                throw new \LogicException('Seules les ventes en brouillon peuvent être finalisées. Statut actuel: ' . $sale->getStatus());
            }

            $lines = $this->saleLineRepository->findBySale($sale->getId());
            if (count($lines) === 0) {
                \Illuminate\Support\Facades\Log::warning('Hardware sale finalization failed: sale has no lines', [
                    'sale_id' => $sale->getId(),
                ]);
                throw new \LogicException('Impossible de finaliser une vente vide');
            }

            $currency = $sale->getCurrency();
            $total = new Money(0, $currency);

            foreach ($lines as $line) {
                $total = $total->add($line->getLineTotal());
            }

            $paid = new Money($paidAmount, $currency);
            if ($paid->getAmount() < 0) {
                throw new \InvalidArgumentException('Le montant payé ne peut pas être négatif');
            }

            // Met à jour les totaux de la vente
            $sale->updateTotals($total, $paid);

            // Prépare un UpdateStockUseCase adapté Hardware (sans batches)
            $productRepository = $this->getProductRepository(); // adapter vers Quincaillerie pour Hardware
            $stockMovementRepository = app(StockMovementRepositoryInterface::class);
            $updateStockUseCase = new HardwareUpdateStockUseCase($productRepository, $stockMovementRepository);

            foreach ($lines as $line) {
                try {
                    $updateStockUseCase->removeStock(
                        $line->getProductId(),
                        $line->getQuantity()->getValue(),
                        $sale->getShopId(),
                        $userId,
                        'SALE-' . $sale->getId()
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Hardware sale finalization failed during stock removal', [
                        'sale_id' => $sale->getId(),
                        'product_id' => $line->getProductId(),
                        'quantity' => $line->getQuantity()->getValue(),
                        'error' => $e->getMessage(),
                    ]);
                    throw new \RuntimeException('Erreur lors de la mise à jour du stock pour le produit: ' . $e->getMessage(), 0, $e);
                }
            }

            $sale->markCompleted();
            $this->saleRepository->save($sale);
            
            \Illuminate\Support\Facades\Log::info('Hardware sale finalized successfully', [
                'sale_id' => $sale->getId(),
                'total' => $total->getAmount(),
                'paid' => $paid->getAmount(),
            ]);
        });
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        try {
            $this->cancelSaleUseCase->execute($id);
            return response()->json(['message' => 'Sale cancelled']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function emailReceipt(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            return response()->json(['message' => 'Vente introuvable'], 404);
        }

        $authUser = $request->user();
        if ($authUser === null) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 403);
        }

        $userModel = UserModel::find($authUser->id);
        $canViewAllSales = $userModel && ($userModel->isRoot() || $userModel->hasPermission('pharmacy.sales.view.all'));
        if (!$canViewAllSales && (int) $sale->getCreatedBy() !== (int) $authUser->id) {
            return response()->json(['message' => 'Vous ne pouvez envoyer que vos propres ventes.'], 403);
        }

        if ($sale->getCustomerId() === null) {
            return response()->json(['message' => 'Aucun client associé à cette vente.'], 422);
        }

        $customer = \App\Models\Customer::find($sale->getCustomerId());
        if (!$customer || empty($customer->email)) {
            return response()->json(['message' => 'Ce client n\'a pas d\'adresse email.'], 422);
        }

        $shop = Shop::find($shopId);
        $shopName = $shop && $shop->name ? $shop->name : config('app.name', 'Boutique');
        $receiptUrl = route('pharmacy.sales.receipt', ['id' => $sale->getId()]);

        Mail::send(
            'emails.pharmacy.sale_receipt',
            [
                'shopName' => $shopName,
                'customerName' => $customer->full_name ?? $customer->name ?? null,
                'saleId' => $sale->getId(),
                'saleDate' => $sale->getCreatedAt()->format('d/m/Y H:i'),
                'totalAmount' => (float) $sale->getTotal()->getAmount(),
                'currency' => $sale->getCurrency(),
                'receiptUrl' => $receiptUrl,
            ],
            function (\Illuminate\Mail\Message $message) use ($customer, $shopName, $sale): void {
                $message->to($customer->email, $customer->full_name ?? null)
                    ->subject('Votre reçu #' . $sale->getId() . ' - ' . $shopName);
            }
        );

        return response()->json([
            'message' => 'Facture envoyée à ' . $customer->email,
        ]);
    }
}
