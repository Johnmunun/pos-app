<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Pharmacy\Models\StockMovementModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;

class MobileHardwareStockController
{
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
        $stockStatus = (string) $request->input('stock_status', '');
        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        $limit = max(1, min(200, (int) $request->input('limit', 100)));
        $offset = max(0, (int) $request->input('offset', 0));

        $query = ProductModel::query()
            ->with('category')
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('barcode', 'like', '%' . $search . '%');
            });
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($stockStatus === 'low') {
            $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock');
        } elseif ($stockStatus === 'out') {
            $query->where(function ($q) {
                $q->whereNull('stock')->orWhere('stock', '<=', 0);
            });
        }

        if ($depotId !== null && $depotId > 0) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id', $depotId)->orWhereNull('depot_id');
            });
        }

        $productRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $productRows->count() > $limit;
        $products = $productRows->take($limit)->map(function (ProductModel $p) {
            return [
                'id' => (string) $p->id,
                'code' => (string) ($p->code ?? ''),
                'name' => (string) $p->name,
                'barcode' => data_get($p, 'barcode'),
                'category_name' => $p->category?->name,
                'stock' => (float) ($p->stock ?? 0),
                'minimum_stock' => (float) ($p->minimum_stock ?? 0),
                'unit' => (string) ($p->type_unite ?? 'UNITE'),
            ];
        })->values();

        return response()->json([
            'products' => $products,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'stock_status' => $stockStatus,
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

    public function movements(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $shopId = $this->resolveShopId($request, $user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $productId = $request->filled('product_id') ? (string) $request->input('product_id') : null;
        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        $limit = max(1, min(200, (int) $request->input('limit', 100)));
        $offset = max(0, (int) $request->input('offset', 0));

        // Hardware currently reuses pharmacy stock movement stream.
        $query = StockMovementModel::query()
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at');

        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        if ($depotId !== null && $depotId > 0) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id', $depotId)->orWhereNull('depot_id');
            });
        }

        $movementRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $movementRows->count() > $limit;
        $movements = $movementRows->take($limit)->map(function (StockMovementModel $m) {
            $product = ProductModel::query()->find($m->product_id);
            return [
                'id' => (string) $m->id,
                'product_id' => (string) $m->product_id,
                'product_name' => $product?->name,
                'type' => (string) $m->type,
                'quantity' => (float) $m->quantity,
                'reference' => $m->reference,
                'depot_id' => data_get($m, 'depot_id'),
                'created_by' => (int) $m->created_by,
                'created_at' => (string) $m->created_at,
            ];
        })->values();

        return response()->json([
            'movements' => $movements,
            'filters' => [
                'product_id' => $productId,
                'depot_id' => $depotId,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $movements->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $movements->count() : null,
            ],
        ], 200);
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

