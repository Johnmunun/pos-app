<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseLineModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;

class MobileCommercePurchasesController
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

        $status = strtolower((string) $request->input('status', ''));
        $limit = max(1, min(200, (int) $request->input('limit', 50)));
        $offset = max(0, (int) $request->input('offset', 0));

        $query = PurchaseModel::query()
            ->where('shop_id', (int) $shopId)
            ->with('supplier')
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        $purchaseRows = $query
            ->offset($offset)
            ->limit($limit + 1)
            ->get();
        $hasMore = $purchaseRows->count() > $limit;
        $purchases = $purchaseRows->take($limit)->map(fn (PurchaseModel $p) => [
                'id' => (string) $p->id,
                'status' => strtoupper((string) ($p->status ?? 'draft')),
                'supplier_id' => (string) $p->supplier_id,
                'supplier_name' => (string) ($p->supplier?->name ?? ''),
                'total_amount' => (float) $p->total_amount,
                'currency' => (string) ($p->currency ?? 'USD'),
                'expected_at' => $p->expected_at?->toISOString(),
                'received_at' => $p->received_at?->toISOString(),
                'created_at' => $p->created_at?->toISOString(),
            ])->values();

        return response()->json([
            'purchase_orders' => $purchases,
            'filters' => [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $purchases->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $purchases->count() : null,
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

        $purchase = PurchaseModel::query()
            ->where('id', $id)
            ->where('shop_id', (int) $shopId)
            ->with('supplier')
            ->first();

        if ($purchase === null) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }

        $lines = PurchaseLineModel::query()
            ->where('purchase_id', $purchase->id)
            ->get()
            ->map(fn (PurchaseLineModel $line) => [
                'id' => (string) $line->id,
                'product_id' => (string) $line->product_id,
                'product_name' => (string) ($line->product_name ?? ''),
                'ordered_quantity' => (float) $line->ordered_quantity,
                'received_quantity' => (float) $line->received_quantity,
                'unit_cost' => (float) $line->unit_cost,
                'line_total' => (float) $line->line_total,
                'currency' => (string) ($purchase->currency ?? 'USD'),
            ])
            ->values();

        return response()->json([
            'purchase_order' => [
                'id' => (string) $purchase->id,
                'status' => strtoupper((string) ($purchase->status ?? 'draft')),
                'supplier_id' => (string) $purchase->supplier_id,
                'supplier_name' => (string) ($purchase->supplier?->name ?? ''),
                'total_amount' => (float) $purchase->total_amount,
                'currency' => (string) ($purchase->currency ?? 'USD'),
                'expected_at' => $purchase->expected_at?->toISOString(),
                'received_at' => $purchase->received_at?->toISOString(),
                'notes' => $purchase->notes,
                'created_at' => $purchase->created_at?->toISOString(),
            ],
            'lines' => $lines,
        ]);
    }

    public function receipt(Request $request, string $id): JsonResponse
    {
        $detail = $this->show($request, $id);
        if ($detail->getStatusCode() !== 200) {
            return $detail;
        }

        /** @var array<string, mixed> $payload */
        $payload = $detail->getData(true);
        $order = $payload['purchase_order'] ?? [];
        $lines = $payload['lines'] ?? [];

        return response()->json([
            'receipt' => [
                'purchase_order_id' => (string) ($order['id'] ?? ''),
                'status' => $order['status'] ?? null,
                'supplier_name' => $order['supplier_name'] ?? null,
                'currency' => $order['currency'] ?? null,
                'total_amount' => (float) ($order['total_amount'] ?? 0),
                'expected_at' => $order['expected_at'] ?? null,
                'received_at' => $order['received_at'] ?? null,
                'notes' => $order['notes'] ?? null,
                'lines' => $lines,
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

