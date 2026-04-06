<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface as GlobalCommerceProductRepositoryInterface;
use Src\Infrastructure\Ecommerce\Http\Concerns\ResolvesEcommerceInventoryScope;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;

class CatalogController
{
    use ResolvesEcommerceInventoryScope;

    public function __construct(
        private readonly GlobalCommerceProductRepositoryInterface $productRepository
    ) {
    }

    public function index(Request $request): Response
    {
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir le catalogue.');
        }

        $shop = $this->ecommerceInventoryShop($request);
        $shopId = (string) $shop->id;
        $gcShopIds = $this->ecommerceGcShopIds($request, $shop);
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        // Utiliser la méthode search du repository GlobalCommerce
        $products = $this->productRepository->search($shopId, $search ?? '', array_filter([
            'category_id' => $categoryId,
            'is_active' => true,
            'is_published_ecommerce' => true,
            'shop_ids' => $gcShopIds,
        ]));

        // Récupérer les images depuis les modèles Eloquent
        $productIds = array_map(fn($p) => $p->getId(), $products);
        $imageMap = [];
        $models = collect();
        if (!empty($productIds)) {
            $models = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::whereIn('id', $productIds)->get();
            foreach ($models as $model) {
                if ($model->image_path) {
                    try {
                        $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
                        $imageMap[$model->id] = $imageService->getUrl($model->image_path, $model->image_type ?: 'upload');
                    } catch (\Throwable $e) {
                        $imageMap[$model->id] = null;
                    }
                }
            }
        }

        $modelsById = collect($models ?? [])->keyBy('id');
        $productsData = array_map(function ($product) use ($imageMap, $modelsById) {
            $model = $modelsById[$product->getId()] ?? null;
            $productType = $model->product_type ?? 'physical';
            $isDigital = $productType === 'digital';
            $galleryUrls = [];
            if ($model && is_array($model->extra_images) && !empty($model->extra_images)) {
                $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
                foreach ($model->extra_images as $extraPath) {
                    try {
                        $galleryUrls[] = $imageService->getUrl($extraPath, 'upload');
                    } catch (\Throwable) {
                    }
                }
            }
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price_amount' => $product->getSalePrice()->getAmount(),
                'price_currency' => $product->getSalePrice()->getCurrency(),
                'stock' => $product->getStock()->getValue(),
                'category_id' => $product->getCategoryId(),
                'image_url' => $imageMap[$product->getId()] ?? null,
                'gallery_urls' => $galleryUrls,
                'sku' => $product->getSku(),
                'product_type' => $productType,
                'is_digital' => $isDigital,
                'download_url' => $isDigital ? ($model->download_url ?? $model->download_path) : null,
                'requires_shipping' => (bool) ($model->requires_shipping ?? true),
            ];
        }, $products);

        // Récupérer les catégories pour le filtre (même périmètre shop_id que les produits)
        $categoriesData = [];
        try {
            $categoriesData = CategoryModel::query()
                ->whereIn('shop_id', $gcShopIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            // Catégories non disponibles
        }

        return Inertia::render('Ecommerce/Catalog/Index', [
            'products' => $productsData,
            'categories' => $categoriesData,
            'filters' => [
                'category_id' => $categoryId,
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les produits.');
        }

        $product = $this->productRepository->findById($id);

        if (!$product) {
            abort(404, 'Produit introuvable.');
        }

        $productModel = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::find($id);
        $typeProduit = $productModel->type_produit ?? 'physique';
        $productType = $productModel->product_type ?? 'physical';
        $isDigital = $typeProduit === 'numerique' || $productType === 'digital';

        $imageUrl = null;
        $galleryUrls = [];
        if ($productModel?->image_path) {
            try {
                $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
                $imageUrl = $imageService->getUrl($productModel->image_path, $productModel->image_type ?: 'upload');
            } catch (\Throwable) {
            }
        }
        if ($productModel && is_array($productModel->extra_images) && !empty($productModel->extra_images)) {
            $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
            foreach ($productModel->extra_images as $extraPath) {
                try {
                    $galleryUrls[] = $imageService->getUrl($extraPath, 'upload');
                } catch (\Throwable) {
                }
            }
        }

        $downloadUrl = null;
        if ($isDigital && $productModel) {
            $downloadUrl = $productModel->lien_telechargement ?? $productModel->download_url ?? ($productModel->download_path ? asset('storage/' . $productModel->download_path) : null);
        }

        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price_amount' => $product->getSalePrice()->getAmount(),
            'price_currency' => $product->getSalePrice()->getCurrency(),
            'stock' => $product->getStock()->getValue(),
            'category_id' => $product->getCategoryId(),
            'image_url' => $imageUrl,
            'gallery_urls' => $galleryUrls,
            'sku' => $product->getSku(),
            'product_type' => $productType,
            'type_produit' => $typeProduit,
            'mode_paiement' => $productModel->mode_paiement ?? 'paiement_immediat',
            'couleur' => $productModel->couleur,
            'taille' => $productModel->taille,
            'is_digital' => $isDigital,
            'download_url' => $downloadUrl,
            'requires_shipping' => (bool) ($productModel->requires_shipping ?? !$isDigital),
        ];

        $shop = $this->ecommerceInventoryShop($request);
        $shopId = (string) $shop->id;
        $gcShopIds = $this->ecommerceGcShopIds($request, $shop);
        $categoriesData = [];
        try {
            $categoriesData = CategoryModel::query()
                ->whereIn('shop_id', $gcShopIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            // Categories not available
        }

        $reviews = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('ecommerce_reviews')) {
            $reviews = \Src\Infrastructure\Ecommerce\Models\ReviewModel::where('product_id', $id)
                ->where('shop_id', $shopId)
                ->where('is_approved', true)
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'customer_name' => $r->customer_name,
                    'rating' => $r->rating,
                    'title' => $r->title,
                    'comment' => $r->comment,
                    'created_at' => $r->created_at?->format('d/m/Y'),
                ])
                ->toArray();
        }

        return Inertia::render('Ecommerce/Catalog/Show', [
            'product' => $productData,
            'categories' => $categoriesData,
            'reviews' => $reviews,
        ]);
    }
}
