<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Pharmacy\Models\CategoryModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Services\ProductImageService;
use App\Models\User as UserModel;

class MobilePharmacyProductsController
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
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
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

        // Pharmacy POS: depot filter -> current depot + central (depot_id = null)
        if ($depotId !== null) {
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
                'product_code' => (string) ($model->code ?? $model->product_code ?? ''),
                'barcode' => $model->barcode ?? null,
                'description' => $model->description ?? '',
                'category_id' => (int) $model->category_id,
                'price_amount' => (float) ($model->price_amount ?? 0),
                'price_currency' => (string) ($model->price_currency ?? 'USD'),
                'unit' => (string) ($model->unit ?? ''),
                'current_stock' => (int) ($model->stock ?? 0),
                'minimum_stock' => (int) ($model->minimum_stock ?? 0),
                'prescription_required' => (bool) ($model->requires_prescription ?? false),
                'dosage' => $model->dosage ?? null,
                'medicine_type' => $model->type ?? null,
                'manufacturer' => $model->manufacturer ?? null,
                'supplier_id' => $model->supplier_id ?? null,
                'is_active' => (bool) ($model->is_active ?? true),
                'image_url' => $this->imageService->getUrlFromPath(
                    $model->image_path ?? null,
                    $model->image_type ?? 'upload'
                ),
            ];
        })->values();

        // Categories for filter UI (active only)
        $categoriesQuery = CategoryModel::query()
            ->where('is_active', true)
            ->orderBy('name');

        // Non-root users should see only their shop categories.
        if (!$user->isRoot()) {
            $categoriesQuery->where('shop_id', $shopId);
        }

        $categories = $categoriesQuery->get()->map(function (CategoryModel $model) {
            return [
                'id' => (int) $model->id,
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
                'id' => (int) $model->id,
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
            // Fallback: user shop_id (common case) or tenant_id.
            $shopId = $user->shop_id !== null
                ? (string) $user->shop_id
                : ($user->tenant_id ? (string) $user->tenant_id : null);
        }

        return $shopId;
    }
}

