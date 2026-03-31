<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Services\ProductImageService;

class MobileCommerceProductsController
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
        $limit = max(1, min(200, (int) $request->input('limit', 100)));
        $offset = max(0, (int) $request->input('offset', 0));

        $query = ProductModel::query()
            ->with('category')
            ->where('shop_id', $shopId)
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('barcode', 'like', '%' . $search . '%');
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

        $productRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $productRows->count() > $limit;
        $products = $productRows->take($limit)->map(function (ProductModel $m) {
            return [
                'id' => (string) $m->id,
                'sku' => (string) $m->sku,
                'barcode' => $m->barcode,
                'name' => (string) $m->name,
                'description' => $m->description,
                'category_id' => (string) $m->category_id,
                'sale_price_amount' => (float) ($m->sale_price_amount ?? 0),
                'sale_price_currency' => (string) ($m->sale_price_currency ?? 'USD'),
                'purchase_price_amount' => (float) ($m->purchase_price_amount ?? 0),
                'purchase_price_currency' => (string) ($m->purchase_price_currency ?? 'USD'),
                'wholesale_price_amount' => $m->wholesale_price_amount !== null ? (float) $m->wholesale_price_amount : null,
                'discount_percent' => $m->discount_percent !== null ? (float) $m->discount_percent : null,
                'stock' => (float) ($m->stock ?? 0),
                'minimum_stock' => (float) ($m->minimum_stock ?? 0),
                'is_weighted' => (bool) ($m->is_weighted ?? false),
                'has_expiration' => (bool) ($m->has_expiration ?? false),
                'is_active' => (bool) ($m->is_active ?? true),
                'image_url' => $m->image_path
                    ? $this->imageService->getUrl($m->image_path, $m->image_type ?? 'upload')
                    : null,
            ];
        })->values();

        $categories = CategoryModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(fn (CategoryModel $c) => [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'description' => (string) ($c->description ?? ''),
            ])
            ->values();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'status' => $status,
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

        $categories = CategoryModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(fn (CategoryModel $c) => [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'description' => (string) ($c->description ?? ''),
            ])
            ->values();

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

