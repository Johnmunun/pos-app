<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcInventoryItemModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcInventoryModel;

class MobileCommerceInventoriesController
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

        $query = GcInventoryModel::query()
            ->where('shop_id', (int) $shopId)
            ->with(['creator:id,name', 'validator:id,name'])
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        $inventoryRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $inventoryRows->count() > $limit;
        $inventories = $inventoryRows->take($limit)->map(fn (GcInventoryModel $inv) => [
            'id' => (string) $inv->id,
            'reference' => (string) $inv->reference,
            'status' => (string) $inv->status,
            'items_count' => (int) ($inv->items_count ?? 0),
            'started_at' => $inv->started_at?->format(DATE_ATOM),
            'validated_at' => $inv->validated_at?->format(DATE_ATOM),
            'created_at' => $inv->created_at->format(DATE_ATOM),
            'creator_name' => (string) ($inv->creator?->name ?? ''),
            'validator_name' => $inv->validator?->name,
        ])->values();

        return response()->json([
            'inventories' => $inventories,
            'filters' => [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $inventories->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $inventories->count() : null,
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

        $inventory = GcInventoryModel::query()
            ->where('id', $id)
            ->where('shop_id', (int) $shopId)
            ->with(['creator:id,name', 'validator:id,name'])
            ->first();

        if ($inventory === null) {
            return response()->json(['message' => 'Inventory not found'], 404);
        }

        $items = GcInventoryItemModel::query()
            ->where('inventory_id', $inventory->id)
            ->with('product:id,name,sku,stock')
            ->get()
            ->map(fn (GcInventoryItemModel $item) => [
                'id' => (string) $item->id,
                'product_id' => (string) $item->product_id,
                'product_name' => (string) ($item->product?->name ?? ''),
                'product_code' => (string) ($item->product?->sku ?? ''),
                'system_quantity' => (float) $item->system_quantity,
                'counted_quantity' => $item->counted_quantity !== null ? (float) $item->counted_quantity : null,
                'difference' => (float) ($item->difference ?? 0),
            ])
            ->values();

        return response()->json([
            'inventory' => [
                'id' => (string) $inventory->id,
                'reference' => (string) $inventory->reference,
                'status' => (string) $inventory->status,
                'started_at' => $inventory->started_at?->format(DATE_ATOM),
                'validated_at' => $inventory->validated_at?->format(DATE_ATOM),
                'created_at' => $inventory->created_at->format(DATE_ATOM),
                'creator_name' => (string) ($inventory->creator?->name ?? ''),
                'validator_name' => $inventory->validator?->name,
            ],
            'items' => $items,
            'stats' => [
                'total_items' => $items->count(),
                'counted_items' => $items->filter(fn ($i) => $i['counted_quantity'] !== null)->count(),
                'items_with_difference' => $items->filter(fn ($i) => abs((float) $i['difference']) > 0.0001)->count(),
            ],
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

