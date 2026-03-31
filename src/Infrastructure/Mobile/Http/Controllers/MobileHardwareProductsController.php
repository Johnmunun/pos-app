<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;
use Src\Infrastructure\Quincaillerie\Services\ProductImageService;

class MobileHardwareProductsController
{
    public function __construct(
        private readonly ProductImageService $imageService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $shopId = $this->resolveShopId($request, $user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $search = (string) $request->input('search', '');
        $categoryId = $request->filled('category_id') ? (string) $request->input('category_id') : null;
        $status = (string) $request->input('status', '');
        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        $limit = max(1, min(200, (int) $request->input('limit', 100)));
        $offset = max(0, (int) $request->input('offset', 0));

        $query = ProductModel::query()
            ->with('category')
            ->where('shop_id', $shopId)
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($status !== '') {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Hardware depot filtering: selected depot + central.
        if ($depotId !== null && $depotId > 0) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id', $depotId)
                    ->orWhereNull('depot_id');
            });
        }

        $productRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $productRows->count() > $limit;
        $products = $productRows->take($limit)->map(function (ProductModel $model) {
            return [
                'id' => (string) $model->id,
                'name' => (string) $model->name,
                'product_code' => (string) ($model->code ?? ''),
                'barcode' => $model->barcode ?? null,
                'description' => $model->description ?? '',
                'category_id' => (string) $model->category_id,
                'price_amount' => (float) ($model->price_amount ?? 0),
                'price_currency' => (string) ($model->price_currency ?? 'USD'),
                'price_normal' => $model->price_normal !== null ? (float) $model->price_normal : null,
                'price_reduced' => $model->price_reduced !== null ? (float) $model->price_reduced : null,
                'price_non_negotiable' => $model->price_non_negotiable !== null ? (float) $model->price_non_negotiable : null,
                'price_wholesale_normal' => $model->price_wholesale_normal !== null ? (float) $model->price_wholesale_normal : null,
                'price_wholesale_reduced' => $model->price_wholesale_reduced !== null ? (float) $model->price_wholesale_reduced : null,
                'price_non_negotiable_wholesale' => $model->price_non_negotiable_wholesale !== null ? (float) $model->price_non_negotiable_wholesale : null,
                'current_stock' => (float) ($model->stock ?? 0),
                'minimum_stock' => (float) ($model->minimum_stock ?? 0),
                'type_unite' => (string) ($model->type_unite ?? 'UNITE'),
                'quantite_par_unite' => (int) ($model->quantite_par_unite ?? 1),
                'est_divisible' => (bool) ($model->est_divisible ?? true),
                'is_active' => (bool) ($model->is_active ?? true),
                'image_url' => $this->imageService->getUrl($model->image_path, $model->image_type ?? 'upload'),
            ];
        })->values();

        $categoriesQuery = CategoryModel::query()
            ->where('is_active', true)
            ->orderBy('name');

        if (!$user->isRoot()) {
            $categoriesQuery->where('shop_id', $shopId);
        }

        $categories = $categoriesQuery->get()->map(function (CategoryModel $model) {
            return [
                'id' => (string) $model->id,
                'name' => (string) $model->name,
                'description' => (string) ($model->description ?? ''),
            ];
        })->values();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'status' => $status,
                'depot_id' => $depotId,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $products->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $products->count() : null,
            ],
        ], 200);
    }

    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $shopId = $this->resolveShopId($request, $user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $query = CategoryModel::query()->where('is_active', true)->orderBy('name');
        if (!$user->isRoot()) {
            $query->where('shop_id', $shopId);
        }

        $categories = $query->get()->map(function (CategoryModel $model) {
            return [
                'id' => (string) $model->id,
                'name' => (string) $model->name,
                'description' => (string) ($model->description ?? ''),
            ];
        })->values();

        return response()->json(['categories' => $categories], 200);
    }

    private function resolveShopId(Request $request, UserModel $user): ?string
    {
        $shopId = null;
        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        if ($depotId && $user->tenant_id !== null) {
            $shopByDepot = Shop::query()
                ->where('depot_id', $depotId)
                ->where('tenant_id', (int) $user->tenant_id)
                ->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }

        if ($shopId === null) {
            $shopId = $user->shop_id !== null
                ? (string) $user->shop_id
                : ($user->tenant_id ? (string) $user->tenant_id : null);
        }

        return $shopId;
    }
}

