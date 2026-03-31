<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleLineModel;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;

class MobileCommerceSalesController
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

        $query = SaleModel::query()
            ->where('shop_id', $shopId)
            ->with('creator:id,name')
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', strtolower($status));
        }

        $salesRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $salesRows->count() > $limit;
        $sales = $salesRows->take($limit)->map(function (SaleModel $m) {
            return [
                'id' => (string) $m->id,
                'status' => strtoupper((string) ($m->status ?? 'completed')),
                'total_amount' => (float) $m->total_amount,
                'currency' => (string) ($m->currency ?? 'USD'),
                'customer_name' => $m->customer_name,
                'seller_name' => $m->creator?->name,
                'created_at' => $m->created_at?->toISOString(),
                'lines_count' => $m->lines()->count(),
            ];
        })->values();

        return response()->json([
            'sales' => $sales,
            'filters' => [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $sales->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $sales->count() : null,
            ],
        ], 200);
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

        $sale = SaleModel::query()
            ->where('id', $id)
            ->where('shop_id', $shopId)
            ->with('creator:id,name')
            ->first();

        if ($sale === null) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        $lines = SaleLineModel::query()
            ->where('sale_id', $sale->id)
            ->get()
            ->map(fn (SaleLineModel $l) => [
                'id' => (string) $l->id,
                'product_id' => (string) $l->product_id,
                'product_name' => (string) ($l->product_name ?? ''),
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'line_total' => (float) $l->subtotal,
            ])
            ->values();

        return response()->json([
            'sale' => [
                'id' => (string) $sale->id,
                'status' => strtoupper((string) ($sale->status ?? 'completed')),
                'total_amount' => (float) $sale->total_amount,
                'currency' => (string) ($sale->currency ?? 'USD'),
                'customer_name' => $sale->customer_name,
                'notes' => $sale->notes,
                'seller_name' => $sale->creator?->name,
                'created_at' => $sale->created_at?->toISOString(),
            ],
            'lines' => $lines,
        ], 200);
    }

    public function receipt(Request $request, string $id): JsonResponse
    {
        $detail = $this->show($request, $id);
        if ($detail->getStatusCode() !== 200) {
            return $detail;
        }

        /** @var array<string, mixed> $payload */
        $payload = $detail->getData(true);
        $sale = $payload['sale'] ?? [];
        $lines = $payload['lines'] ?? [];

        return response()->json([
            'receipt' => [
                'sale_id' => (string) ($sale['id'] ?? ''),
                'created_at' => $sale['created_at'] ?? null,
                'status' => $sale['status'] ?? null,
                'currency' => $sale['currency'] ?? null,
                'total_amount' => (float) ($sale['total_amount'] ?? 0),
                'customer_name' => $sale['customer_name'] ?? null,
                'seller_name' => $sale['seller_name'] ?? null,
                'notes' => $sale['notes'] ?? null,
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

