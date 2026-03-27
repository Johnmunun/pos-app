<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use Inertia\Inertia;
use Src\Application\GlobalCommerce\Sales\DTO\CreateSaleDTO;
use Src\Application\GlobalCommerce\Sales\UseCases\CreateSaleUseCase;
use Src\Domain\GlobalCommerce\Sales\Repositories\SaleRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Services\ProductImageService;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Support\Facades\Schema;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;
use Src\Shared\ValueObjects\Quantity;
use Src\Application\Billing\Services\FeatureLimitService;

class GcSaleController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $depotId = $request->session()->get('current_depot_id');

        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', (int) $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }

        if ($user->tenant_id) {
            return (string) $user->tenant_id;
        }

        abort(403, 'Shop ID not found.');
    }

    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private ProductRepositoryInterface $productRepository,
        private CreateSaleUseCase $createSaleUseCase,
        private GetStoreSettingsUseCase $getStoreSettingsUseCase,
        private StoreLogoService $storeLogoService,
        private FeatureLimitService $featureLimitService
    ) {
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $models = SaleModel::with('creator')
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        $list = $models->map(fn (SaleModel $m) => [
            'id' => (string) $m->id,
            'status' => strtoupper((string) ($m->status ?? 'completed')),
            'total_amount' => (float) $m->total_amount,
            'currency' => $m->currency,
            'customer_name' => $m->customer_name,
            'seller_name' => $m->creator?->name ?? '—',
            'created_at' => $m->created_at?->format('Y-m-d H:i'),
            'lines_count' => $m->lines()->count(),
        ])->values()->all();
        return Inertia::render('Commerce/Sales/Index', [
            'sales' => $list,
        ]);
    }

    public function show(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $model = SaleModel::with(['lines', 'creator'])->where('id', $id)->where('shop_id', $shopId)->first();
        if (!$model) {
            return redirect()->route('commerce.sales.index')->with('error', 'Vente introuvable.');
        }
        $lines = $model->lines->map(fn ($l) => [
            'product_name' => $l->product_name,
            'quantity' => (float) $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'subtotal' => (float) $l->subtotal,
        ])->values()->all();
        return Inertia::render('Commerce/Sales/Show', [
            'sale' => [
                'id' => (string) $model->id,
                'status' => strtoupper((string) ($model->status ?? 'completed')),
                'total_amount' => (float) $model->total_amount,
                'currency' => $model->currency,
                'customer_name' => $model->customer_name,
                'notes' => $model->notes,
                'seller_name' => $model->creator?->name ?? '—',
                'created_at' => $model->created_at?->format('d/m/Y H:i'),
                'lines' => $lines,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $authUser = $request->user();
        $canUseWholesale = $authUser !== null && ($authUser->isRoot() || $authUser->hasPermission('pharmacy.sales.wholesale'));

        /** @var ProductImageService $imageService */
        $imageService = app(ProductImageService::class);

        // Utiliser le modèle Eloquent pour inclure image/wholesale/is_weighted directement
        // et retourner un payload cohérent avec l'UI POS (price_amount/price_currency/code/...).
        $cols = [
            'id',
            'sku',
            'barcode',
            'name',
            'category_id',
            'sale_price_amount',
            'sale_price_currency',
            'wholesale_price_amount',
            'stock',
            'image_path',
            'image_type',
            'is_weighted',
        ];
        if (Schema::hasColumn('gc_products', 'min_sale_price_amount')) {
            $cols[] = 'min_sale_price_amount';
        }
        if (Schema::hasColumn('gc_products', 'min_wholesale_price_amount')) {
            $cols[] = 'min_wholesale_price_amount';
        }

        $models = ProductModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get($cols);

        $productList = $models->map(fn (ProductModel $m) => [
            'id' => (string) $m->id,
            'sku' => $m->sku,
            'code' => $m->sku,
            'barcode' => $m->barcode,
            'name' => $m->name,
            'category_id' => $m->category_id,
            // Champs utilisés par l'UI POS Commerce
            'price_amount' => (float) ($m->sale_price_amount ?? 0),
            'price_currency' => $m->sale_price_currency ?? 'USD',
            'wholesale_price_amount' => $m->wholesale_price_amount,
            'min_sale_price_amount' => $m->min_sale_price_amount ?? null,
            'min_wholesale_price_amount' => $m->min_wholesale_price_amount ?? null,
            'stock' => (float) ($m->stock ?? 0),
            'image_url' => $m->image_path
                ? $imageService->getUrl($m->image_path, $m->image_type ?? 'upload')
                : null,
            'est_divisible' => (bool) ($m->is_weighted ?? false),
            'type_unite' => (bool) ($m->is_weighted ?? false) ? 'POIDS' : 'UNITE',
            // Compat legacy (au cas où)
            'sale_price' => (float) ($m->sale_price_amount ?? 0),
            'currency' => $m->sale_price_currency ?? 'USD',
        ])->values()->all();

        $categories = CategoryModel::where('shop_id', $shopId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Clients liés au tenant (pas au shop). Récupérer tenant_id depuis user ou depuis le shop.
        $tenantId = $authUser?->tenant_id;
        if (($tenantId === null || $tenantId === '') && Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::find($shopId);
            $tenantId = $shop?->tenant_id ?? $shopId;
        }
        $tenantId = $tenantId ?? $shopId;
        $tenantId = (int) $tenantId; // Cohérence pour la requête (customers.tenant_id = bigint)
        // Même requête que GcCustomerController::index (sans filtre is_active) pour cohérence
        $customers = Customer::where('tenant_id', $tenantId)
            ->orderBy('full_name')
            ->limit(100)
            ->get(['id', 'full_name', 'phone', 'email'])
            ->map(fn (Customer $c) => [
                'id' => (string) $c->id,
                'full_name' => $c->full_name,
                'phone' => $c->phone ?? '',
                'email' => $c->email ?? '',
            ])
            ->values()
            ->all();

        $firstProduct = $productList[0] ?? null;
        return Inertia::render('Commerce/Sales/Create', [
            'products' => $productList,
            'currency' => $firstProduct['price_currency'] ?? $firstProduct['currency'] ?? 'USD',
            'categories' => $categories,
            'customers' => $customers,
            'canUseWholesale' => $canUseWholesale,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        
        // Log pour déboguer
        $linesInput = $request->input('lines', []);
        \Log::debug('GcSaleController::store - Données reçues', [
            'lines' => $linesInput,
            'lines_count' => count($linesInput),
            'first_line' => $linesInput[0] ?? null,
            'first_line_keys' => $linesInput[0] ? array_keys($linesInput[0]) : [],
            'first_line_quantity' => $linesInput[0]['quantity'] ?? 'MISSING',
            'request_all' => $request->all(),
        ]);
        
        // Nettoyer et valider les lignes avant validation Laravel
        $cleanedLines = [];
        foreach ($linesInput as $index => $line) {
            if (!is_array($line)) {
                \Log::warning("Ligne {$index} n'est pas un tableau", ['line' => $line]);
                continue;
            }
            
            // S'assurer que quantity existe et est valide
            if (!isset($line['quantity']) || $line['quantity'] === null || $line['quantity'] === '') {
                \Log::warning("Ligne {$index} n'a pas de quantity", ['line' => $line]);
                continue;
            }
            
            $quantity = (float) $line['quantity'];
            if ($quantity <= 0 || is_nan($quantity)) {
                \Log::warning("Ligne {$index} a une quantity invalide", ['quantity' => $line['quantity'], 'line' => $line]);
                continue;
            }
            
            // Créer une ligne propre
            $cleanedLine = [
                'product_id' => $line['product_id'] ?? '',
                'quantity' => $quantity,
            ];
            
            if (isset($line['unit_price']) && $line['unit_price'] !== null && $line['unit_price'] !== '') {
                $cleanedLine['unit_price'] = (float) $line['unit_price'];
            }
            
            if (isset($line['discount_percent']) && $line['discount_percent'] !== null && $line['discount_percent'] !== '') {
                $cleanedLine['discount_percent'] = (float) $line['discount_percent'];
            }
            
            $cleanedLines[] = $cleanedLine;
        }
        
        // Remplacer les lignes dans la requête
        $request->merge(['lines' => $cleanedLines]);
        
        if (empty($cleanedLines)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Aucune ligne valide après nettoyage.'], 422);
            }
            return redirect()->back()->withErrors(['lines' => 'Aucune ligne valide.'])->withInput();
        }
        
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
            'sale_mode' => 'nullable|in:retail,wholesale',
            'draft' => 'nullable|boolean',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|uuid|exists:gc_products,id',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'customer_id' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $lines = array_values(array_filter($validated['lines'], fn ($l) => ((float) ($l['quantity'] ?? 0)) > 0));
        if (empty($lines)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Au moins une ligne avec quantité > 0.'], 422);
            }
            return redirect()->back()->withErrors(['lines' => 'Au moins une ligne avec quantité > 0.'])->withInput();
        }

        $customerName = $validated['customer_name'] ?? null;
        if (empty($customerName) && !empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            if ($customer && (string) $customer->tenant_id === (string) ($request->user()?->tenant_id ?? $shopId)) {
                $customerName = $customer->full_name;
            }
        }

        $isDraft = (bool) ($validated['draft'] ?? false);
        $dto = new CreateSaleDTO(
            $shopId,
            $lines,
            strtoupper($validated['currency']),
            $customerName,
            $validated['notes'] ?? null,
            $request->user()?->id,
            $isDraft
        );
        try {
            if (!$isDraft) {
                $tenantId = $request->user()?->tenant_id ? (string) $request->user()->tenant_id : null;
                if ($tenantId === null) {
                    $shopRow = Shop::query()->find($shopId);
                    $tenantId = $shopRow ? (string) $shopRow->tenant_id : null;
                }
                $this->featureLimitService->assertCanRecordSale($tenantId);
            }
            $sale = $this->createSaleUseCase->execute($dto);

            if (!$isDraft) {
                app(\App\Services\AppNotificationService::class)->notifySaleCompleted(
                    $sale->getTotalAmount(),
                    $sale->getCurrency(),
                    $sale->getId(),
                    $sale->getCustomerName(),
                    $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
                );
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'sale' => [
                        'id' => $sale->getId(),
                        'status' => $sale->getStatus(),
                        'total_amount' => $sale->getTotalAmount(),
                        'currency' => $sale->getCurrency(),
                        'customer_name' => $sale->getCustomerName(),
                        'notes' => $sale->getNotes(),
                    ],
                ]);
            }

            return redirect()->route('commerce.sales.index')->with('success', 'Vente enregistrée.');
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Compat POS (Pharmacy/Hardware) : dans Commerce, la vente est déjà enregistrée en "completed"
     * lors du store(). Cet endpoint évite une erreur côté frontend quand il appelle finalize().
     */
    public function finalize(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $request->validate([
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $model = SaleModel::where('id', $id)->where('shop_id', $shopId)->first();
        if (!$model) {
            return response()->json(['message' => 'Vente introuvable.'], 404);
        }

        // Si la vente était en brouillon, déduire le stock puis passer à completed.
        if (strtolower((string) $model->status) === 'draft') {
            foreach ($model->lines as $line) {
                $product = $this->productRepository->findById($line->product_id);
                if ($product && (string) $product->getShopId() === (string) $shopId) {
                    $product->removeStock(new Quantity((float) $line->quantity));
                    $this->productRepository->update($product);
                }
            }
        }

        $model->status = 'completed';
        $model->save();

        app(\App\Services\AppNotificationService::class)->notifySaleCompleted(
            (float) $model->total_amount,
            (string) ($model->currency ?? 'USD'),
            (string) $model->id,
            $model->customer_name,
            $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
        );

        return response()->json([
            'success' => true,
            'sale' => [
                'id' => (string) $model->id,
                'status' => (string) $model->status,
                'total_amount' => (float) $model->total_amount,
                'currency' => (string) $model->currency,
            ],
        ]);
    }

    /**
     * Reçu thermique pour impression (80mm). Compatible avec l'impression auto si activée dans les paramètres.
     */
    public function receipt(Request $request, string $id)
    {
        $shopId = $this->getShopId($request);
        $model = SaleModel::with(['lines', 'creator'])->where('id', $id)->where('shop_id', $shopId)->first();
        if (!$model) {
            abort(404);
        }

        $shop = Shop::find($shopId);
        $currency = $model->currency ?? 'CDF';

        $linesData = $model->lines->map(fn ($l) => [
            'product_name' => $l->product_name,
            'quantity' => (float) $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'line_total' => (float) $l->subtotal,
            'currency' => $currency,
        ])->values()->all();

        $settings = $this->getStoreSettingsUseCase->execute($shopId);
        $logoUrl = $settings && $settings->getLogoPath()
            ? $this->storeLogoService->getUrl($settings->getLogoPath())
            : null;

        // Numéro de reçu lisible (entier) au lieu de l'UUID
        $receiptNumber = abs(crc32($model->id)) % 1000000;

        // Infos boutique : priorité store_settings, sinon shop
        $shopName = $shop ? $shop->name : 'Boutique';
        $address = $settings?->getAddress();
        $shopAddress = $address?->getStreet() ?? $shop?->address;
        $shopCity = $address?->getCity() ?? $shop?->city;
        $shopPostalCode = $address?->getPostalCode() ?? $shop?->postal_code;
        $shopCountry = $address?->getCountry() ?? $shop?->country;
        $shopPhone = $settings?->getPhone() ?? $shop?->phone;
        $shopEmail = $settings?->getEmail() ?? $shop?->email;

        return view('commerce.receipt', [
            'shop_name' => $shopName,
            'shop_address' => $shopAddress,
            'shop_city' => $shopCity,
            'shop_postal_code' => $shopPostalCode,
            'shop_country' => $shopCountry,
            'shop_phone' => $shopPhone,
            'shop_email' => $shopEmail,
            'logo_url' => $logoUrl,
            'receipt_number' => $receiptNumber,
            'sale' => [
                'id' => $model->id,
                'created_at' => $model->created_at?->format('d/m/Y H:i'),
                'total_amount' => (float) $model->total_amount,
                'paid_amount' => (float) $model->total_amount,
                'balance_amount' => 0.0,
                'currency' => $currency,
                'seller_name' => $model->creator?->name ?? '—',
            ],
            'lines' => $linesData,
            'customer' => $model->customer_name,
        ]);
    }

    /**
     * Création rapide d'un client depuis le POS (compat Pharmacy/Hardware).
     */
    public function quickCreateCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);
        $authUser = $request->user();
        if (!$authUser || !$authUser->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 403);
        }
        $tenantId = (int) $authUser->tenant_id;
        $name = trim($request->input('name'));
        $phone = $request->filled('phone') ? trim($request->input('phone')) : null;
        $email = $request->filled('email') ? trim($request->input('email')) : null;

        try {
            $code = 'C' . now()->format('YmdHis') . substr(uniqid(), -4);
            $customer = Customer::create([
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
}
