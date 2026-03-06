<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Ramsey\Uuid\Uuid;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockMovementModel;

class GcStockController
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

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);

        $query = ProductModel::byShop($shopId)
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name');

        $filters = [
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'stock_status' => $request->input('stock_status'),
        ];

        if ($filters['search']) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', '%' . $s . '%')
                    ->orWhere('sku', 'like', '%' . $s . '%')
                    ->orWhere('barcode', 'like', '%' . $s . '%');
            });
        }

        if ($filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        if ($filters['stock_status']) {
            if ($filters['stock_status'] === 'low') {
                $query->where('stock', '>', 0)
                    ->whereColumn('stock', '<=', 'minimum_stock');
            } elseif ($filters['stock_status'] === 'out') {
                $query->where(function ($q) {
                    $q->whereNull('stock')->orWhere('stock', '<=', 0);
                });
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginator = $query->paginate($perPage)->appends($request->query());

        $products = $paginator->getCollection()->map(function (ProductModel $p) {
            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category_name' => $p->category?->name,
                'stock' => (float) ($p->stock ?? 0),
                'minimum_stock' => (float) ($p->minimum_stock ?? 0),
                'is_active' => (bool) $p->is_active,
            ];
        })->toArray();

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        $lowStockCount = ProductModel::byShop($shopId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        return Inertia::render('Commerce/Stock/Index', [
            'products' => $products,
            'pagination' => $pagination,
            'filters' => $filters,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    public function adjust(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $data = $request->validate([
            'type' => 'required|string|in:IN,OUT,ADJUSTMENT',
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        $product = ProductModel::query()
            ->where('shop_id', $shopId)
            ->where('id', $id)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Produit introuvable.'], 404);
        }

        $qty = (float) $data['quantity'];
        $before = (float) ($product->stock ?? 0);
        $delta = $data['type'] === 'IN'
            ? $qty
            : ($data['type'] === 'OUT' ? -$qty : $qty);
        $after = max(0, $before + $delta);

        $product->stock = $after;
        $product->save();

        GcStockMovementModel::create([
            'id' => Uuid::uuid4()->toString(),
            'shop_id' => (int) $shopId,
            'product_id' => $product->id,
            'type' => $data['type'],
            'quantity' => abs($delta),
            'reference' => 'Ajustement manuel',
            'reference_type' => 'manual_adjustment',
            'reference_id' => null,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Stock mis à jour.',
            'product' => [
                'id' => $product->id,
                'stock' => $after,
            ],
        ]);
    }
}

