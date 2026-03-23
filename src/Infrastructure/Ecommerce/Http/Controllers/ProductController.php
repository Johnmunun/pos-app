<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Services\ProductImageService;
use Src\Application\GlobalCommerce\Inventory\DTO\CreateProductDTO;
use Src\Application\GlobalCommerce\Inventory\DTO\UpdateProductDTO;
use Src\Application\GlobalCommerce\Inventory\UseCases\CreateProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\UpdateProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\DeleteProductUseCase;
use Src\Application\Billing\Services\FeatureLimitService;

class ProductController
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductImageService $imageService,
        private readonly CreateProductUseCase $createProductUseCase,
        private readonly UpdateProductUseCase $updateProductUseCase,
        private readonly DeleteProductUseCase $deleteProductUseCase,
        private readonly FeatureLimitService $featureLimitService,
    ) {}

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) abort(403, 'Shop ID not found.');
        if ($isRoot && !$shopId) abort(403, 'Please select a shop first.');
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les produits.');
        }

        $shopId = $this->getShopId($request);
        $search = $request->input('search', '');
        $categoryId = $request->input('category_id');

        $products = $this->productRepository->search($shopId, $search, array_filter([
            'category_id' => $categoryId,
        ]));

        $productIds = array_map(fn ($p) => $p->getId(), $products);
        $models = ProductModel::whereIn('id', $productIds)->get()->keyBy('id');

        // Catégories pour le filtre (simple liste à plat)
        $categoryModels = \Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel::where('shop_id', $shopId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $productsData = [];
        foreach ($products as $p) {
            $m = $models[$p->getId()] ?? null;
            $imageUrl = null;
            if ($m?->image_path) {
                try {
                    $imageUrl = $this->imageService->getUrl($m->image_path, $m->image_type ?? 'upload');
                } catch (\Throwable) {}
            }
            $galleryUrls = [];
            if ($m && is_array($m->extra_images) && !empty($m->extra_images)) {
                foreach ($m->extra_images as $extraPath) {
                    try {
                        $galleryUrls[] = $this->imageService->getUrl($extraPath, 'upload');
                    } catch (\Throwable) {
                        // ignore broken image
                    }
                }
            }
            $productsData[] = [
                'id' => $p->getId(),
                'sku' => $p->getSku(),
                'barcode' => $p->getBarcode(),
                'name' => $p->getName(),
                'description' => $p->getDescription(),
                'sale_price' => $p->getSalePrice()->getAmount(),
                'purchase_price' => $p->getPurchasePrice()->getAmount(),
                'minimum_stock' => $p->getMinimumStock()->getValue(),
                'currency' => $p->getSalePrice()->getCurrency(),
                'stock' => $p->getStock()->getValue(),
                'category_id' => $p->getCategoryId(),
                'is_active' => $p->isActive(),
                'is_weighted' => $p->isWeighted(),
                'has_expiration' => $p->hasExpiration(),
                'product_type' => $m ? ($m->product_type ?? 'simple') : 'simple',
                'unit' => $m ? ($m->unit ?? 'PIECE') : 'PIECE',
                'wholesale_price' => $m ? $m->wholesale_price_amount : null,
                'min_sale_price' => $m ? $m->min_sale_price_amount : null,
                'min_wholesale_price' => $m ? $m->min_wholesale_price_amount : null,
                'discount_percent' => $m ? $m->discount_percent : null,
                'price_non_negotiable' => (bool) ($m ? ($m->price_non_negotiable ?? false) : false),
                'weight' => $m ? $m->weight : null,
                'length' => $m ? $m->length : null,
                'width' => $m ? $m->width : null,
                'height' => $m ? $m->height : null,
                'tax_rate' => $m ? $m->tax_rate : null,
                'tax_type' => $m ? $m->tax_type : null,
                'status' => $m ? ($m->status ?? ($p->isActive() ? 'active' : 'inactive')) : ($p->isActive() ? 'active' : 'inactive'),
                'download_url' => $m ? $m->download_url : null,
                'requires_shipping' => (bool) ($m ? ($m->requires_shipping ?? true) : true),
                'couleur' => $m ? $m->couleur : null,
                'taille' => $m ? $m->taille : null,
                'type_produit' => $m ? ($m->type_produit ?? 'physique') : 'physique',
                'mode_paiement' => $m ? ($m->mode_paiement ?? 'paiement_immediat') : 'paiement_immediat',
                'lien_telechargement' => $m ? $m->lien_telechargement : null,
                'is_published_ecommerce' => (bool) ($m ? ($m->is_published_ecommerce ?? false) : false),
                'image_url' => $imageUrl,
                'gallery_urls' => $galleryUrls,
            ];
        }

        $categoriesData = $categoryModels
            ->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])
            ->values()
            ->all();

        return Inertia::render('Ecommerce/Products/Index', [
            'products' => $productsData,
            'categories' => $categoriesData,
            'filters' => ['search' => $search, 'category_id' => $categoryId],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasPermission('ecommerce.product.create')
                && !$user->hasPermission('ecommerce.product.manage')
                && !$user->hasPermission('module.ecommerce'))) {
            abort(403, 'Vous n\'avez pas la permission de créer des produits.');
        }

        $this->featureLimitService->assertCanCreateProduct(
            $user->tenant_id !== null ? (string) $user->tenant_id : null
        );

        $shopId = $this->getShopId($request);
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
            'gallery' => 'nullable|array|max:3',
            'gallery.*' => 'image|mimes:jpeg,jpg,png,webp|max:2048',
            'wholesale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'min_wholesale_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'price_non_negotiable' => 'nullable|boolean',
            'product_type' => 'nullable|string|in:simple,variable,service,digital',
            'couleur' => 'nullable|string|max:100',
            'taille' => 'nullable|string|max:100',
            'type_produit' => 'nullable|string|in:physique,numerique',
            'mode_paiement' => 'nullable|string|in:paiement_immediat,paiement_livraison',
            'lien_telechargement' => 'nullable|string|url|max:2048',
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
            (bool) ($validated['has_expiration'] ?? false)
        );
        $product = $this->createProductUseCase->execute($dto);

        app(\App\Services\AppNotificationService::class)->notifyProductCreated(
            'Ecommerce',
            $product->getName(),
            $product->getSku(),
            $this->getShopId($request),
            $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
        );

        /** @var ProductModel|null $model */
        $model = ProductModel::find($product->getId());
        if ($model) {
            $extra = [];

            if (array_key_exists('wholesale_price', $validated) && $validated['wholesale_price'] !== null) {
                $extra['wholesale_price_amount'] = (float) $validated['wholesale_price'];
            }
            if (array_key_exists('min_sale_price', $validated) && $validated['min_sale_price'] !== null) {
                $extra['min_sale_price_amount'] = (float) $validated['min_sale_price'];
            }
            if (array_key_exists('min_wholesale_price', $validated) && $validated['min_wholesale_price'] !== null) {
                $extra['min_wholesale_price_amount'] = (float) $validated['min_wholesale_price'];
            }
            if (array_key_exists('discount_percent', $validated) && $validated['discount_percent'] !== null) {
                $extra['discount_percent'] = (float) $validated['discount_percent'];
            }
            $extra['price_non_negotiable'] = (bool) ($validated['price_non_negotiable'] ?? false);
            if (array_key_exists('product_type', $validated)) {
                $extra['product_type'] = $validated['product_type'] ?: null;
            }
            if (array_key_exists('couleur', $validated)) {
                $extra['couleur'] = $validated['couleur'] ?: null;
            }
            if (array_key_exists('taille', $validated)) {
                $extra['taille'] = $validated['taille'] ?: null;
            }
            if (array_key_exists('type_produit', $validated)) {
                $extra['type_produit'] = $validated['type_produit'] ?: null;
                if (($validated['type_produit'] ?? '') === 'numerique') {
                    $extra['requires_shipping'] = false;
                }
            }
            if (array_key_exists('mode_paiement', $validated)) {
                $extra['mode_paiement'] = $validated['mode_paiement'] ?: null;
            }
            if (array_key_exists('lien_telechargement', $validated)) {
                $extra['lien_telechargement'] = $validated['lien_telechargement'] ?: null;
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

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $stored = $this->imageService->store($file);
                $extra['image_path'] = $stored['image_path'];
                $extra['image_type'] = $stored['image_type'];
            }

            if ($request->hasFile('gallery')) {
                $galleryFiles = $request->file('gallery');
                $galleryPaths = [];
                foreach ($galleryFiles as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $stored = $this->imageService->store($file);
                        $galleryPaths[] = $stored['image_path'];
                    }
                }
                if (!empty($galleryPaths)) {
                    $extra['extra_images'] = $galleryPaths;
                }
            }

            /** @phpstan-ignore-next-line */
            if (!empty($extra)) {
                $model->update($extra);
            }
        }

        return redirect()->route('ecommerce.products.index')->with('success', 'Produit créé.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasPermission('ecommerce.product.update')
                && !$user->hasPermission('ecommerce.product.manage')
                && !$user->hasPermission('module.ecommerce'))) {
            abort(403, 'Vous n\'avez pas la permission de modifier des produits.');
        }

        $shopId = $this->getShopId($request);
        $validated = $request->validate([
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|uuid|exists:gc_categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'numeric|min:0',
            'currency' => 'string|in:USD,EUR,CDF',
            'is_weighted' => 'boolean',
            'has_expiration' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'gallery' => 'nullable|array|max:3',
            'gallery.*' => 'image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_gallery' => 'nullable|boolean',
            'download_url' => 'nullable|string|url|max:2048',
            'download_file' => 'nullable|file|mimes:pdf,mp3,mp4,zip,doc,docx,xls,xlsx,txt|max:51200',
            'remove_download' => 'nullable|boolean',
            'couleur' => 'nullable|string|max:100',
            'taille' => 'nullable|string|max:100',
            'type_produit' => 'nullable|string|in:physique,numerique',
            'mode_paiement' => 'nullable|string|in:paiement_immediat,paiement_livraison',
            'lien_telechargement' => 'nullable|string|url|max:2048',
            'requires_shipping' => 'nullable|boolean',
            'wholesale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'min_wholesale_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'price_non_negotiable' => 'nullable|boolean',
            'product_type' => 'nullable|string|in:simple,variable,service,digital',
            'unit' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_type' => 'nullable|string|in:included,excluded',
            'status' => 'nullable|string|in:active,inactive,draft',
        ]);

        $status = $validated['status'] ?? 'active';
        $isActive = $status === 'active';

        $stock = null;
        if (array_key_exists('initial_stock', $validated) && $validated['initial_stock'] !== '' && $validated['initial_stock'] !== null) {
            $stock = (float) $validated['initial_stock'];
        }

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
            $stock,
            $validated['currency'] ?? 'USD',
            (bool) ($validated['is_weighted'] ?? false),
            (bool) ($validated['has_expiration'] ?? false),
            $isActive
        );

        $product = $this->updateProductUseCase->execute($dto);

        /** @var ProductModel|null $model */
        $model = ProductModel::find($product->getId());
        if ($model) {
            $extra = [];
            $currentGallery = is_array($model->extra_images) ? $model->extra_images : [];

            if (array_key_exists('wholesale_price', $validated) && $validated['wholesale_price'] !== null) {
                $extra['wholesale_price_amount'] = (float) $validated['wholesale_price'];
            }
            if (array_key_exists('min_sale_price', $validated) && $validated['min_sale_price'] !== null) {
                $extra['min_sale_price_amount'] = (float) $validated['min_sale_price'];
            }
            if (array_key_exists('min_wholesale_price', $validated) && $validated['min_wholesale_price'] !== null) {
                $extra['min_wholesale_price_amount'] = (float) $validated['min_wholesale_price'];
            }
            if (array_key_exists('discount_percent', $validated) && $validated['discount_percent'] !== null) {
                $extra['discount_percent'] = (float) $validated['discount_percent'];
            }
            $extra['price_non_negotiable'] = (bool) ($validated['price_non_negotiable'] ?? $model->price_non_negotiable);
            if (array_key_exists('product_type', $validated)) {
                $extra['product_type'] = $validated['product_type'] ?: null;
            }
            if (array_key_exists('couleur', $validated)) {
                $extra['couleur'] = $validated['couleur'] ?: null;
            }
            if (array_key_exists('taille', $validated)) {
                $extra['taille'] = $validated['taille'] ?: null;
            }
            if (array_key_exists('type_produit', $validated)) {
                $extra['type_produit'] = $validated['type_produit'] ?: null;
                if (($validated['type_produit'] ?? '') === 'numerique') {
                    $extra['requires_shipping'] = false;
                }
            }
            if (array_key_exists('mode_paiement', $validated)) {
                $extra['mode_paiement'] = $validated['mode_paiement'] ?: null;
            }
            if (array_key_exists('lien_telechargement', $validated)) {
                $extra['lien_telechargement'] = $validated['lien_telechargement'] ?: null;
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
            if (array_key_exists('requires_shipping', $validated) && ($validated['type_produit'] ?? '') !== 'numerique') {
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

            if ($request->hasFile('gallery')) {
                // Supprimer les anciennes images de galerie
                foreach ($currentGallery as $oldPath) {
                    $this->imageService->delete($oldPath, 'upload');
                }
                $galleryFiles = $request->file('gallery');
                $galleryPaths = [];
                foreach ($galleryFiles as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $stored = $this->imageService->store($file);
                        $galleryPaths[] = $stored['image_path'];
                    }
                }
                $extra['extra_images'] = $galleryPaths;
            } elseif (!empty($validated['remove_gallery'])) {
                foreach ($currentGallery as $oldPath) {
                    $this->imageService->delete($oldPath, 'upload');
                }
                $extra['extra_images'] = [];
            }

            /** @phpstan-ignore-next-line */
            if (!empty($extra)) {
                $model->update($extra);
            }
        }

        return redirect()->route('ecommerce.products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        try {
            $this->deleteProductUseCase->execute($shopId, $id);
            return redirect()->route('ecommerce.products.index')->with('success', 'Produit supprimé.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('ecommerce.products.index')->with('error', $e->getMessage());
        }
    }
}
