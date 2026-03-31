<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockTransferModel;

class MobileCommerceTransfersController
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

        $status = (string) $request->input('status', '');
        $limit = max(1, min(200, (int) $request->input('limit', 50)));
        $offset = max(0, (int) $request->input('offset', 0));

        $query = GcStockTransferModel::query()
            ->where(function ($q) use ($shopId) {
                $q->where('from_shop_id', (int) $shopId)
                    ->orWhere('to_shop_id', (int) $shopId);
            })
            ->with(['creator:id,name', 'validator:id,name', 'fromShop:id,name', 'toShop:id,name'])
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        $transferRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $transferRows->count() > $limit;
        $transfers = $transferRows->take($limit)->map(fn (GcStockTransferModel $t) => [
            'id' => (string) $t->id,
            'reference' => (string) $t->reference,
            'status' => (string) $t->status,
            'from_shop_id' => (int) $t->from_shop_id,
            'from_shop_name' => (string) ($t->fromShop?->name ?? ''),
            'to_shop_id' => (int) $t->to_shop_id,
            'to_shop_name' => (string) ($t->toShop?->name ?? ''),
            'created_by_name' => (string) ($t->creator?->name ?? ''),
            'validated_by_name' => $t->validator?->name,
            'validated_at' => $t->validated_at?->format(DATE_ATOM),
            'created_at' => $t->created_at?->format(DATE_ATOM),
            'notes' => $t->notes,
            'total_items' => $t->items()->count(),
            'total_quantity' => (float) $t->items()->sum('quantity'),
        ])->values();

        return response()->json([
            'transfers' => $transfers,
            'filters' => [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $transfers->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $transfers->count() : null,
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $shopId = $this->resolveShopId($request, $user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $transfer = GcStockTransferModel::query()
            ->where('id', $id)
            ->where(function ($q) use ($shopId) {
                $q->where('from_shop_id', (int) $shopId)
                    ->orWhere('to_shop_id', (int) $shopId);
            })
            ->with([
                'creator:id,name',
                'validator:id,name',
                'fromShop:id,name',
                'toShop:id,name',
                'items.product:id,name,sku,stock',
            ])
            ->first();

        if ($transfer === null) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        $items = $transfer->items->map(fn ($item) => [
            'id' => (string) $item->id,
            'product_id' => (string) $item->product_id,
            'product_name' => (string) ($item->product?->name ?? ''),
            'product_code' => (string) ($item->product?->sku ?? ''),
            'current_stock' => (float) ($item->product?->stock ?? 0),
            'quantity' => (float) $item->quantity,
        ])->values();

        return response()->json([
            'transfer' => [
                'id' => (string) $transfer->id,
                'reference' => (string) $transfer->reference,
                'status' => (string) $transfer->status,
                'from_shop_id' => (int) $transfer->from_shop_id,
                'from_shop_name' => (string) ($transfer->fromShop?->name ?? ''),
                'to_shop_id' => (int) $transfer->to_shop_id,
                'to_shop_name' => (string) ($transfer->toShop?->name ?? ''),
                'created_by_name' => (string) ($transfer->creator?->name ?? ''),
                'validated_by_name' => $transfer->validator?->name,
                'validated_at' => $transfer->validated_at?->format(DATE_ATOM),
                'created_at' => $transfer->created_at?->format(DATE_ATOM),
                'notes' => $transfer->notes,
                'total_items' => $items->count(),
                'total_quantity' => (float) $items->sum('quantity'),
            ],
            'items' => $items,
        ]);
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

