<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Application\GlobalCommerce\Inventory\DTO\CreateProductDTO;
use Src\Application\GlobalCommerce\Inventory\DTO\UpdateProductDTO;
use Src\Application\GlobalCommerce\Inventory\UseCases\CreateProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\DeleteProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\UpdateProductUseCase;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Services\ProductImageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Shop;
use App\Services\TenantBackofficeShopResolver;

class GcProductController
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private CreateProductUseCase $createProductUseCase,
        private UpdateProductUseCase $updateProductUseCase,
        private DeleteProductUseCase $deleteProductUseCase,
        private ProductImageService $imageService,
        private FeatureLimitService $featureLimitService,
        private TenantBackofficeShopResolver $shopResolver,
    ) {}

    /**
     * @return array{shop: Shop, shopId: string, gcIds: list<string>}
     */
    private function gcScope(Request $request): array
    {
        $shop = $this->shopResolver->resolveShop($request);
        $user = $request->user();
        $tenantId = $user && $user->tenant_id !== null && $user->tenant_id !== '' ? (string) $user->tenant_id : null;

        return [
            'shop' => $shop,
            'shopId' => (string) $shop->id,
            'gcIds' => $this->shopResolver->globalCommerceInventoryShopIds($shop, $tenantId),
        ];
    }

    public function index(Request $request): Response
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $search = (string) $request->input('search', '');
        $categoryId = $request->input('category_id');
        $status = $request->input('status');
        
        // Convertir le statut en is_active
        $isActive = null;
        if ($status === 'active') {
            $isActive = true;
        } elseif ($status === 'inactive') {
            $isActive = false;
        }
        
        $products = $this->productRepository->search($shopId, $search, array_filter([
            'category_id' => $categoryId,
            'is_active' => $isActive,
            'shop_ids' => $gcIds,
        ]));

        // L'écran Index ouvre un drawer d'édition. On doit donc inclure les champs nécessaires
        // (prix d'achat/vente, description, etc.) et les champs avancés stockés sur gc_products.
        $ids = array_map(fn ($p) => $p->getId(), $products);
        $extraMap = [];
        if (!empty($ids)) {
            $cols = ['id', 'image_path', 'image_type', 'wholesale_price_amount', 'discount_percent', 'price_non_negotiable'];
            if (Schema::hasColumn('gc_products', 'min_sale_price_amount')) {
                $cols[] = 'min_sale_price_amount';
            }
            if (Schema::hasColumn('gc_products', 'min_wholesale_price_amount')) {
                $cols[] = 'min_wholesale_price_amount';
            }
            foreach (['product_type', 'download_url', 'download_path', 'requires_shipping', 'unit', 'weight', 'length', 'width', 'height', 'tax_rate', 'tax_type', 'status'] as $c) {
                if (Schema::hasColumn('gc_products', $c)) {
                    $cols[] = $c;
                }
            }
            // S'assurer que 'unit' est toujours inclus
            if (!in_array('unit', $cols) && Schema::hasColumn('gc_products', 'unit')) {
                $cols[] = 'unit';
            }

            $models = ProductModel::whereIn('id', $ids)->get($cols);
            foreach ($models as $m) {
                /** @var ProductModel $m */
                $extraMap[$m->id] = [
                    'image_url' => $m->image_path
                        ? $this->imageService->getUrl($m->image_path, $m->image_type ?? 'upload')
                        : null,
                    'wholesale_price_amount' => $m->wholesale_price_amount,
                    'discount_percent' => $m->discount_percent,
                    'price_non_negotiable' => (bool) ($m->price_non_negotiable ?? false),
                    'min_sale_price_amount' => $m->min_sale_price_amount ?? null,
                    'min_wholesale_price_amount' => $m->min_wholesale_price_amount ?? null,
                    'product_type' => $m->product_type ?? null,
                    'download_url' => $m->download_url ?? null,
                    'download_path' => $m->download_path ?? null,
                    'requires_shipping' => (bool) ($m->requires_shipping ?? true),
                    'unit' => $m->unit ?? null,
                    'weight' => $m->weight ?? null,
                    'length' => $m->length ?? null,
                    'width' => $m->width ?? null,
                    'height' => $m->height ?? null,
                    'tax_rate' => $m->tax_rate ?? null,
                    'tax_type' => $m->tax_type ?? null,
                    'status' => $m->status ?? 'active',
                ];
            }
        }

        $list = array_map(function ($p) use ($extraMap) {
            $id = $p->getId();
            $extra = $extraMap[$id] ?? [];
            return [
                'id' => $id,
                'sku' => $p->getSku(),
                'barcode' => $p->getBarcode(),
                'name' => $p->getName(),
                'description' => $p->getDescription(),
                'category_id' => $p->getCategoryId(),
                'purchase_price' => $p->getPurchasePrice()->getAmount(),
                'purchase_price_currency' => $p->getPurchasePrice()->getCurrency(),
                'sale_price_amount' => $p->getSalePrice()->getAmount(),
                'sale_price_currency' => $p->getSalePrice()->getCurrency(),
                'sale_price' => $p->getSalePrice()->getAmount(),
                'currency' => $p->getSalePrice()->getCurrency(),
                'stock' => $p->getStock()->getValue(),
                'minimum_stock' => $p->getMinimumStock()->getValue(),
                'is_weighted' => $p->isWeighted(),
                'has_expiration' => $p->hasExpiration(),
                'is_active' => $p->isActive(),
                'image_url' => $extra['image_url'] ?? null,
                'wholesale_price' => $extra['wholesale_price_amount'] ?? null,
                'wholesale_price_amount' => $extra['wholesale_price_amount'] ?? null,
                'discount_percent' => $extra['discount_percent'] ?? null,
                'price_non_negotiable' => $extra['price_non_negotiable'] ?? false,
                'min_sale_price_amount' => $extra['min_sale_price_amount'] ?? null,
                'min_wholesale_price_amount' => $extra['min_wholesale_price_amount'] ?? null,
                'product_type' => $extra['product_type'] ?? null,
                'download_url' => $extra['download_url'] ?? null,
                'download_path' => $extra['download_path'] ?? null,
                'requires_shipping' => $extra['requires_shipping'] ?? null,
                'unit' => $extra['unit'] ?? null,
                'weight' => $extra['weight'] ?? null,
                'length' => $extra['length'] ?? null,
                'width' => $extra['width'] ?? null,
                'height' => $extra['height'] ?? null,
                'tax_rate' => $extra['tax_rate'] ?? null,
                'tax_type' => $extra['tax_type'] ?? null,
                'status' => $extra['status'] ?? 'active',
            ];
        }, $products);
        $categories = CategoryModel::whereIn('shop_id', $gcIds)->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Commerce/Products/Index', [
            'products' => $list,
            'categories' => $categories,
            'filters' => ['search' => $search, 'category_id' => $categoryId],
        ]);
    }

    public function create(Request $request): Response
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $categories = CategoryModel::whereIn('shop_id', $gcIds)->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Commerce/Products/Create', [
            'categories' => $categories,
            'currency' => 'USD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->featureLimitService->assertCanCreateProduct(
            $user && $user->tenant_id !== null ? (string) $user->tenant_id : null
        );

        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $validated = $request->validate([
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|uuid|exists:gc_categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'initial_stock' => 'numeric|min:0',
            'minimum_stock' => 'numeric|min:0',
            'currency' => 'string|in:USD,EUR,CDF',
            'is_weighted' => 'boolean',
            'has_expiration' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'wholesale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'min_wholesale_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'price_non_negotiable' => 'nullable|boolean',
            'product_type' => 'nullable|string|in:simple,variable,service,digital',
            'download_url' => 'nullable|string|url|max:2048',
            'download_file' => 'nullable|file|mimes:pdf,mp3,mp4,zip,doc,docx,xls,xlsx,txt|max:51200',
            'requires_shipping' => 'nullable|boolean',
            'unit' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_type' => 'nullable|string|in:included,excluded',
            'status' => 'nullable|string|in:active,inactive,draft',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'slug' => 'nullable|string|max:180',
        ]);
        $dto = new CreateProductDTO(
            $shopId,
            $validated['sku'],
            $validated['barcode'] ?? null,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['category_id'],
            (float) $validated['purchase_price'],
            (float) $validated['sale_price'],
            (float) ($validated['initial_stock'] ?? 0),
            (float) ($validated['minimum_stock'] ?? 0),
            $validated['currency'] ?? 'USD',
            (bool) ($validated['is_weighted'] ?? false),
            (bool) ($validated['has_expiration'] ?? false),
            $gcIds
        );
        $product = $this->createProductUseCase->execute($dto);

        app(\App\Services\AppNotificationService::class)->notifyProductCreated(
            'Commerce',
            $product->getName(),
            $product->getSku(),
            $shopId,
            $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
        );

        // Enregistrer les champs avancés (image, prix de gros, remise, non négociable)
        /** @var ProductModel|null $model */
        $model = ProductModel::find($product->getId());
        if ($model) {
            $extra = [];

            if (isset($validated['wholesale_price']) && $validated['wholesale_price'] !== null) {
                $extra['wholesale_price_amount'] = (float) $validated['wholesale_price'];
            }
            if (isset($validated['min_sale_price']) && $validated['min_sale_price'] !== null) {
                $extra['min_sale_price_amount'] = (float) $validated['min_sale_price'];
            }
            if (isset($validated['min_wholesale_price']) && $validated['min_wholesale_price'] !== null) {
                $extra['min_wholesale_price_amount'] = (float) $validated['min_wholesale_price'];
            }
            if (isset($validated['discount_percent']) && $validated['discount_percent'] !== null) {
                $extra['discount_percent'] = (float) $validated['discount_percent'];
            }
            $extra['price_non_negotiable'] = (bool) ($validated['price_non_negotiable'] ?? false);
            if (array_key_exists('product_type', $validated)) {
                $extra['product_type'] = $validated['product_type'] ?: null;
            }
            if (array_key_exists('download_url', $validated) && $validated['download_url']) {
                $extra['download_url'] = $validated['download_url'];
                $extra['download_path'] = null;
                $extra['requires_shipping'] = false;
            }
            if ($request->hasFile('download_file')) {
                $file = $request->file('download_file');
                $path = $file->store('digital_products', 'public');
                $extra['download_path'] = $path;
                $extra['download_url'] = null;
                $extra['requires_shipping'] = false;
            }
            if (array_key_exists('requires_shipping', $validated)) {
                $extra['requires_shipping'] = (bool) $validated['requires_shipping'];
            }
            if (array_key_exists('unit', $validated)) {
                $extra['unit'] = $validated['unit'] ?: null;
            }
            if (array_key_exists('weight', $validated)) {
                $extra['weight'] = $validated['weight'] !== null && $validated['weight'] !== '' ? (float) $validated['weight'] : null;
            }
            if (array_key_exists('length', $validated)) {
                $extra['length'] = $validated['length'] !== null && $validated['length'] !== '' ? (float) $validated['length'] : null;
            }
            if (array_key_exists('width', $validated)) {
                $extra['width'] = $validated['width'] !== null && $validated['width'] !== '' ? (float) $validated['width'] : null;
            }
            if (array_key_exists('height', $validated)) {
                $extra['height'] = $validated['height'] !== null && $validated['height'] !== '' ? (float) $validated['height'] : null;
            }
            if (array_key_exists('tax_rate', $validated)) {
                $extra['tax_rate'] = $validated['tax_rate'] !== null && $validated['tax_rate'] !== '' ? (float) $validated['tax_rate'] : null;
            }
            if (array_key_exists('tax_type', $validated)) {
                $extra['tax_type'] = $validated['tax_type'] ?: null;
            }
            if (array_key_exists('status', $validated)) {
                $extra['status'] = $validated['status'] ?? 'active';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'meta_title') && array_key_exists('meta_title', $validated)) {
                $extra['meta_title'] = $validated['meta_title'] !== '' ? $validated['meta_title'] : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'meta_description') && array_key_exists('meta_description', $validated)) {
                $extra['meta_description'] = $validated['meta_description'] !== '' ? $validated['meta_description'] : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'slug') && array_key_exists('slug', $validated)) {
                $extra['slug'] = $validated['slug'] !== '' ? $validated['slug'] : null;
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $stored = $this->imageService->store($file);
                $extra['image_path'] = $stored['image_path'];
                $extra['image_type'] = $stored['image_type'];
            }

            if ($extra !== []) {
                $model->update($extra);
            }
        }
        return redirect()->route('commerce.products.index')->with('success', 'Produit créé.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $product = $this->productRepository->findById($id);
        if (!$product || !in_array($product->getShopId(), $gcIds, true)) {
            return redirect()->route('commerce.products.index')->with('error', 'Produit introuvable.');
        }
        $model = ProductModel::find($product->getId());
        $categories = CategoryModel::whereIn('shop_id', $gcIds)->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Commerce/Products/Edit', [
            'product' => [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'barcode' => $product->getBarcode(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'category_id' => $product->getCategoryId(),
                'purchase_price' => $product->getPurchasePrice()->getAmount(),
                'sale_price' => $product->getSalePrice()->getAmount(),
                'minimum_stock' => $product->getMinimumStock()->getValue(),
                'currency' => $product->getSalePrice()->getCurrency(),
                'is_weighted' => $product->isWeighted(),
                'has_expiration' => $product->hasExpiration(),
                'is_active' => $product->isActive(),
                'wholesale_price' => $model?->wholesale_price_amount,
                'discount_percent' => $model?->discount_percent,
                'price_non_negotiable' => (bool) ($model?->price_non_negotiable ?? false),
                'min_sale_price' => $model?->min_sale_price_amount,
                'min_wholesale_price' => $model?->min_wholesale_price_amount,
                'product_type' => $model?->product_type,
                'download_url' => $model?->download_url,
                'download_path' => $model?->download_path,
                'requires_shipping' => (bool) ($model->requires_shipping ?? true),
                'unit' => $model?->unit,
                'weight' => $model?->weight,
                'length' => $model?->length,
                'width' => $model?->width,
                'height' => $model?->height,
                'tax_rate' => $model?->tax_rate,
                'tax_type' => $model?->tax_type,
                'status' => $model?->status ?? 'active',
                'image_url' => $model && $model->image_path
                    ? app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class)
                        ->getUrl($model->image_path, $model->image_type ?? 'upload')
                    : null,
            ],
            'categories' => $categories,
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $scope = $this->gcScope($request);
        $gcIds = $scope['gcIds'];
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé.'], 404);
        }
        
        // Vérifier que le produit appartient au shop
        if (!in_array($product->getShopId(), $gcIds, true)) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }
        
        // Toggle le statut
        if ($product->isActive()) {
            $product->markInactive();
        } else {
            $product->markActive();
        }
        
        $this->productRepository->save($product);
        
        // Mettre à jour aussi le modèle pour synchroniser is_active
        $model = ProductModel::find($id);
        if ($model) {
            $model->update(['is_active' => $product->isActive()]);
        }
        
        return response()->json([
            'success' => true,
            'message' => $product->isActive() ? 'Produit activé avec succès.' : 'Produit désactivé avec succès.',
            'is_active' => $product->isActive(),
        ]);
    }

    public function toggleEcommercePublish(Request $request, string $id): JsonResponse
    {
        $scope = $this->gcScope($request);
        $gcIds = $scope['gcIds'];

        /** @var ProductModel|null $model */
        $model = ProductModel::whereIn('shop_id', $gcIds)->find($id);

        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé.'], 404);
        }

        $current = (bool) ($model->is_published_ecommerce ?? false);
        $model->is_published_ecommerce = !$current;
        $model->save();

        return response()->json([
            'success' => true,
            'message' => $model->is_published_ecommerce
                ? 'Produit publié sur la boutique.'
                : 'Produit retiré de la boutique.',
            'is_published_ecommerce' => (bool) $model->is_published_ecommerce,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $validated = $request->validate([
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|uuid|exists:gc_categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'minimum_stock' => 'numeric|min:0',
            'currency' => 'string|in:USD,EUR,CDF',
            'is_weighted' => 'boolean',
            'has_expiration' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'download_url' => 'nullable|string|url|max:2048',
            'download_file' => 'nullable|file|mimes:pdf,mp3,mp4,zip,doc,docx,xls,xlsx,txt|max:51200',
            'remove_download' => 'nullable|boolean',
            'requires_shipping' => 'nullable|boolean',
            'wholesale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'min_wholesale_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'price_non_negotiable' => 'nullable|boolean',
            'product_type' => 'nullable|string|in:simple,variable,service,digital',
            'download_url' => 'nullable|string|url|max:2048',
            'download_file' => 'nullable|file|mimes:pdf,mp3,mp4,zip,doc,docx,xls,xlsx,txt|max:51200',
            'requires_shipping' => 'nullable|boolean',
            'unit' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_type' => 'nullable|string|in:included,excluded',
            'status' => 'nullable|string|in:active,inactive,draft',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'slug' => 'nullable|string|max:180',
        ]);
        $status = $validated['status'] ?? 'active';
        $isActive = $status === 'active';
        $dto = new UpdateProductDTO(
            $id,
            $shopId,
            $validated['sku'],
            $validated['barcode'] ?? null,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['category_id'],
            (float) $validated['purchase_price'],
            (float) $validated['sale_price'],
            (float) ($validated['minimum_stock'] ?? 0),
            null,
            $validated['currency'] ?? 'USD',
            (bool) ($validated['is_weighted'] ?? false),
            (bool) ($validated['has_expiration'] ?? false),
            $isActive,
            $gcIds
        );
        $product = $this->updateProductUseCase->execute($dto);

        /** @var ProductModel|null $model */
        $model = ProductModel::find($product->getId());
        if ($model) {
            $extra = [];

            if (isset($validated['wholesale_price']) && $validated['wholesale_price'] !== null) {
                $extra['wholesale_price_amount'] = (float) $validated['wholesale_price'];
            }
            if (isset($validated['min_sale_price']) && $validated['min_sale_price'] !== null) {
                $extra['min_sale_price_amount'] = (float) $validated['min_sale_price'];
            }
            if (isset($validated['min_wholesale_price']) && $validated['min_wholesale_price'] !== null) {
                $extra['min_wholesale_price_amount'] = (float) $validated['min_wholesale_price'];
            }
            if (isset($validated['discount_percent']) && $validated['discount_percent'] !== null) {
                $extra['discount_percent'] = (float) $validated['discount_percent'];
            }
            $extra['price_non_negotiable'] = (bool) ($validated['price_non_negotiable'] ?? $model->price_non_negotiable);
            if (array_key_exists('product_type', $validated)) {
                $extra['product_type'] = $validated['product_type'] ?: null;
            }
            if (array_key_exists('unit', $validated)) {
                $extra['unit'] = $validated['unit'] ?: null;
            }
            if (array_key_exists('weight', $validated)) {
                $extra['weight'] = $validated['weight'] !== null && $validated['weight'] !== '' ? (float) $validated['weight'] : null;
            }
            if (array_key_exists('length', $validated)) {
                $extra['length'] = $validated['length'] !== null && $validated['length'] !== '' ? (float) $validated['length'] : null;
            }
            if (array_key_exists('width', $validated)) {
                $extra['width'] = $validated['width'] !== null && $validated['width'] !== '' ? (float) $validated['width'] : null;
            }
            if (array_key_exists('height', $validated)) {
                $extra['height'] = $validated['height'] !== null && $validated['height'] !== '' ? (float) $validated['height'] : null;
            }
            if (array_key_exists('tax_rate', $validated)) {
                $extra['tax_rate'] = $validated['tax_rate'] !== null && $validated['tax_rate'] !== '' ? (float) $validated['tax_rate'] : null;
            }
            if (array_key_exists('tax_type', $validated)) {
                $extra['tax_type'] = $validated['tax_type'] ?: null;
            }
            if (array_key_exists('status', $validated)) {
                $extra['status'] = $validated['status'] ?? 'active';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'meta_title') && array_key_exists('meta_title', $validated)) {
                $extra['meta_title'] = $validated['meta_title'] !== '' ? $validated['meta_title'] : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'meta_description') && array_key_exists('meta_description', $validated)) {
                $extra['meta_description'] = $validated['meta_description'] !== '' ? $validated['meta_description'] : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'slug') && array_key_exists('slug', $validated)) {
                $extra['slug'] = $validated['slug'] !== '' ? $validated['slug'] : null;
            }

            if (array_key_exists('download_url', $validated) && $validated['download_url']) {
                $extra['download_url'] = $validated['download_url'];
                $extra['download_path'] = null;
                $extra['requires_shipping'] = false;
            }
            if (!empty($validated['remove_download'])) {
                if ($model->download_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($model->download_path);
                }
                $extra['download_path'] = null;
                $extra['download_url'] = null;
                $extra['requires_shipping'] = true;
            } elseif ($request->hasFile('download_file')) {
                if ($model->download_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($model->download_path);
                }
                $file = $request->file('download_file');
                $path = $file->store('digital_products', 'public');
                $extra['download_path'] = $path;
                $extra['download_url'] = null;
                $extra['requires_shipping'] = false;
            }
            if (array_key_exists('requires_shipping', $validated)) {
                $extra['requires_shipping'] = (bool) $validated['requires_shipping'];
            }
            if (!empty($validated['remove_image'])) {
                $this->imageService->delete($model->image_path, $model->image_type ?? 'upload');
                $extra['image_path'] = null;
                $extra['image_type'] = null;
            } elseif ($request->hasFile('image')) {
                $file = $request->file('image');
                $this->imageService->delete($model->image_path, $model->image_type ?? 'upload');
                $stored = $this->imageService->store($file);
                $extra['image_path'] = $stored['image_path'];
                $extra['image_type'] = $stored['image_type'];
            }

            if ($extra !== []) {
                $model->update($extra);
            }
        }
        return redirect()->route('commerce.products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $gcIds = $this->gcScope($request)['gcIds'];
        try {
            $this->deleteProductUseCase->execute($gcIds, $id);
            return redirect()->route('commerce.products.index')->with('success', 'Produit supprimé.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('commerce.products.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Modèle Excel pour l'import de produits Global Commerce.
     */
    public function importTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Produits Commerce');

        $headers = [
            'sku',
            'nom',
            'categorie',
            'prix_achat',
            'prix_vente',
            'stock',
            'stock_minimum',
            'devise',
            'actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Exemple
        $sheet->fromArray(
            [
                'SKU-001',
                'Produit démo',
                'OUTILLAGE',
                10000,
                15000,
                100,
                5,
                'USD',
                'oui',
            ],
            null,
            'A2'
        );

        $filename = 'modele_import_produits_commerce_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import simple de produits Global Commerce.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format .xlsx ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $handle = fopen($path, 'r');
                $line = $handle ? fgets($handle) : null;
                if ($handle) {
                    fclose($handle);
                }
                $delimiter = $line !== null && strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('GcProduct import: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'Fichier vide.',
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        $required = ['sku', 'nom', 'categorie', 'prix_vente'];
        foreach ($required as $col) {
            if (!in_array($col, $headerRow, true)) {
                return response()->json([
                    'message' => "Colonne obligatoire manquante : '{$col}'.",
                    'total' => count($dataRows),
                    'success' => 0,
                    'failed' => count($dataRows),
                    'errors' => [],
                ], 422);
            }
        }

        // Précharger les catégories
        $categories = CategoryModel::whereIn('shop_id', $gcIds)->get(['id', 'name']);
        $categoriesByName = [];
        foreach ($categories as $cat) {
            $categoriesByName[mb_strtolower($cat->name)] = $cat->id;
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];

            $sku = $rowAssoc['sku'] ?? '';
            $name = $rowAssoc['nom'] ?? '';
            $categoryRaw = $rowAssoc['categorie'] ?? '';

            if ($sku === '') {
                $lineErrors[] = 'SKU obligatoire.';
            }
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }
            if ($categoryRaw === '') {
                $lineErrors[] = 'Catégorie obligatoire.';
            }

            $categoryId = null;
            if ($categoryRaw !== '') {
                $category = $categories->firstWhere('id', $categoryRaw);
                if ($category) {
                    $categoryId = $category->id;
                } else {
                    $lower = mb_strtolower($categoryRaw);
                    if (isset($categoriesByName[$lower])) {
                        $categoryId = $categoriesByName[$lower];
                    }
                }
                if ($categoryId === null) {
                    $lineErrors[] = "Catégorie introuvable : {$categoryRaw}.";
                }
            }

            $purchasePrice = $rowAssoc['prix_achat'] !== ''
                ? (float) str_replace(',', '.', (string) $rowAssoc['prix_achat'])
                : 0.0;
            $salePrice = $rowAssoc['prix_vente'] !== ''
                ? (float) str_replace(',', '.', (string) $rowAssoc['prix_vente'])
                : 0.0;
            if ($salePrice < 0) {
                $lineErrors[] = 'Prix de vente doit être positif.';
            }

            $stock = $rowAssoc['stock'] !== ''
                ? (float) str_replace(',', '.', (string) $rowAssoc['stock'])
                : 0.0;
            $minStock = $rowAssoc['stock_minimum'] !== ''
                ? (float) str_replace(',', '.', (string) $rowAssoc['stock_minimum'])
                : 0.0;
            $currency = $rowAssoc['devise'] !== ''
                ? strtoupper((string) $rowAssoc['devise'])
                : 'USD';
            $activeRaw = mb_strtolower($rowAssoc['actif'] ?? '');
            $isActive = !in_array($activeRaw, ['non', 'no', '0', 'false'], true);

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $dto = new CreateProductDTO(
                    $shopId,
                    $sku,
                    null,
                    $name,
                    null,
                    $categoryId,
                    $purchasePrice,
                    $salePrice,
                    $stock,
                    $minStock,
                    $currency,
                    false,
                    false,
                    $gcIds
                );
                $product = $this->createProductUseCase->execute($dto);

                /** @var ProductModel|null $model */
                $model = ProductModel::find($product->getId());
                if ($model) {
                    $model->update(['is_active' => $isActive]);
                }

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import produits terminé.',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format .xlsx ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $scope = $this->gcScope($request);
        $gcIds = $scope['gcIds'];
        $file = $request->file('file');
        $path = $file->getRealPath();

        // 1) Construire un aperçu brut (quelques lignes) pour affichage tableau
        $sampleHeader = [];
        $sampleRows = [];
        $sheet = null;
        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                // Détecter le délimiteur de manière simple
                $line = fgets(fopen($path, 'r'));
                $delimiter = strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $allRows = $sheet->toArray();
            $sampleHeader = $allRows[0] ?? [];
            $dataRows = array_slice($allRows, 1);
            $sampleRows = array_slice($dataRows, 0, 20);
        } catch (\Throwable $e) {
            Log::warning('GcProduct import preview: failed to build sample', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if ($sheet === null) {
            return response()->json([
                'message' => 'Impossible de charger le fichier.',
            ], 422);
        }

        // 2) Valider les données sans les insérer
        try {
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('GcProduct import preview: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'errors' => [],
                'sample' => [
                    'header' => [],
                    'rows' => [],
                ],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        $required = ['sku', 'nom', 'categorie', 'prix_vente'];
        foreach ($required as $col) {
            if (!in_array($col, $headerRow, true)) {
                return response()->json([
                    'message' => "Colonne obligatoire manquante : '{$col}'.",
                    'total' => count($dataRows),
                    'valid' => 0,
                    'invalid' => count($dataRows),
                    'errors' => [],
                    'sample' => [
                        'header' => $sampleHeader,
                        'rows' => $sampleRows,
                    ],
                ], 422);
            }
        }

        // Précharger les catégories
        $categories = CategoryModel::whereIn('shop_id', $gcIds)->get(['id', 'name']);
        $categoriesByName = [];
        foreach ($categories as $cat) {
            $categoriesByName[mb_strtolower($cat->name)] = $cat->id;
        }

        $valid = 0;
        $invalid = 0;
        $errorsDetailed = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];

            $sku = $rowAssoc['sku'] ?? '';
            $name = $rowAssoc['nom'] ?? '';
            $categoryRaw = $rowAssoc['categorie'] ?? '';

            if ($sku === '') {
                $lineErrors[] = 'SKU obligatoire.';
            }
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }
            if ($categoryRaw === '') {
                $lineErrors[] = 'Catégorie obligatoire.';
            }

            $categoryId = null;
            if ($categoryRaw !== '') {
                $category = $categories->firstWhere('id', $categoryRaw);
                if ($category) {
                    $categoryId = $category->id;
                } else {
                    $lower = mb_strtolower($categoryRaw);
                    if (isset($categoriesByName[$lower])) {
                        $categoryId = $categoriesByName[$lower];
                    }
                }
                if ($categoryId === null) {
                    $lineErrors[] = "Catégorie introuvable : {$categoryRaw}.";
                }
            }

            $salePrice = $rowAssoc['prix_vente'] !== ''
                ? (float) str_replace(',', '.', (string) $rowAssoc['prix_vente'])
                : 0.0;
            if ($salePrice < 0) {
                $lineErrors[] = 'Prix de vente doit être positif.';
            }

            if (!empty($lineErrors)) {
                $invalid++;
                $errorsDetailed[] = [
                    'line' => $lineNum,
                    'field' => null,
                    'message' => implode(' | ', $lineErrors),
                ];
            } else {
                $valid++;
            }
        }

        return response()->json([
            'total' => count($dataRows),
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errorsDetailed,
            'sample' => [
                'header' => $sampleHeader,
                'rows' => $sampleRows,
            ],
        ]);
    }
}
