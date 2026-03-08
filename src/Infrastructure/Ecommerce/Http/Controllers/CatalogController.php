<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface as GlobalCommerceProductRepositoryInterface;

class CatalogController
{
    public function __construct(
        private readonly GlobalCommerceProductRepositoryInterface $productRepository
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $userModel = \App\Models\User::find($user->id);
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
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir le catalogue.');
        }

        $shopId = $this->getShopId($request);
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        // Utiliser la méthode search du repository GlobalCommerce
        $products = $this->productRepository->search($shopId, $search ?? '', array_filter([
            'category_id' => $categoryId,
            'is_active' => true,
        ]));

        // Récupérer les images depuis les modèles Eloquent
        $productIds = array_map(fn($p) => $p->getId(), $products);
        $imageMap = [];
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

        $productsData = array_map(function ($product) use ($imageMap) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price_amount' => $product->getSalePrice()->getAmount(),
                'price_currency' => $product->getSalePrice()->getCurrency(),
                'stock' => $product->getStock()->getValue(),
                'category_id' => $product->getCategoryId(),
                'image_url' => $imageMap[$product->getId()] ?? null,
                'sku' => $product->getSku(),
            ];
        }, $products);

        // Récupérer les catégories pour le filtre
        $categoriesData = [];
        try {
            $categoryRepo = app(\Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface::class);
            $categories = $categoryRepo->findByShop($shopId, ['active' => true]);
            $categoriesData = array_map(function ($category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }, $categories);
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

        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price_amount' => $product->getPrice()->getAmount(),
            'price_currency' => $product->getPrice()->getCurrency(),
            'stock' => $product->getStock()->getValue(),
            'category_id' => $product->getCategoryId(),
            'image_url' => $product->getImageUrl(),
            'sku' => $product->getSku(),
        ];

        // Map categories for filter
        $categoriesData = [];
        try {
            $categoryRepo = app(\Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface::class);
            $categories = $categoryRepo->findByShop($shopId, ['active' => true]);
            $categoriesData = array_map(function ($category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }, $categories);
        } catch (\Throwable $e) {
            // Categories not available
        }

        return Inertia::render('Ecommerce/Catalog/Show', [
            'product' => $productData,
            'categories' => $categoriesData,
        ]);
    }
}
