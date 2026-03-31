<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Pharmacy\Models\ProductModel as PharmacyProductModel;
use Src\Infrastructure\Pharmacy\Models\PurchaseOrderLineModel as PharmacyPurchaseOrderLineModel;
use Src\Infrastructure\Pharmacy\Models\PurchaseOrderModel as PharmacyPurchaseOrderModel;
use Src\Infrastructure\Pharmacy\Models\SupplierModel as PharmacySupplierModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as HardwareProductModel;
use Src\Infrastructure\Quincaillerie\Models\PurchaseOrderLineModel as HardwarePurchaseOrderLineModel;
use Src\Infrastructure\Quincaillerie\Models\PurchaseOrderModel as HardwarePurchaseOrderModel;
use Src\Infrastructure\Quincaillerie\Models\SupplierModel as HardwareSupplierModel;

class MobilePharmacyPurchasesController
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

        $status = strtoupper((string) $request->input('status', ''));
        $limit = max(1, min(200, (int) $request->input('limit', 50)));
        $offset = max(0, (int) $request->input('offset', 0));
        $module = $this->moduleFromRequest($request);
        $isHardware = $module === 'hardware';

        $query = $isHardware
            ? HardwarePurchaseOrderModel::query()->where('shop_id', (int) $shopId)->with('supplier')
            : PharmacyPurchaseOrderModel::query()->where('shop_id', $shopId)->with('supplier');

        if ($status !== '') {
            $query->where('status', $status);
        }

        $orderRows = $query
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();
        $hasMore = $orderRows->count() > $limit;
        $orders = $orderRows->take($limit)->map(fn ($po) => [
                'id' => (string) $po->id,
                'status' => (string) $po->status,
                'supplier_id' => $po->supplier_id ? (string) $po->supplier_id : null,
                'supplier_name' => (string) ($po->supplier?->name ?? ''),
                'total_amount' => (float) $po->total_amount,
                'currency' => (string) $po->currency,
                'ordered_at' => $po->ordered_at?->format(DATE_ATOM),
                'expected_at' => $po->expected_at?->format(DATE_ATOM),
                'received_at' => $po->received_at?->format(DATE_ATOM),
                'created_at' => $po->created_at->format(DATE_ATOM),
            ])->values();

        return response()->json([
            'purchase_orders' => $orders,
            'filters' => [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $orders->count(),
                'has_more' => $hasMore,
                'next_offset' => $hasMore ? $offset + $orders->count() : null,
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

        $module = $this->moduleFromRequest($request);
        $isHardware = $module === 'hardware';

        $order = $isHardware
            ? HardwarePurchaseOrderModel::query()->where('id', $id)->where('shop_id', (int) $shopId)->with('supplier')->first()
            : PharmacyPurchaseOrderModel::query()->where('id', $id)->where('shop_id', $shopId)->with('supplier')->first();

        if ($order === null) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }

        $lines = $isHardware
            ? HardwarePurchaseOrderLineModel::query()->where('purchase_order_id', $order->id)->get()
            : PharmacyPurchaseOrderLineModel::query()->where('purchase_order_id', $order->id)->get();

        $linesData = $lines->map(function ($line) use ($isHardware) {
            $product = $isHardware
                ? HardwareProductModel::query()->find($line->product_id)
                : PharmacyProductModel::query()->find($line->product_id);

            return [
                'id' => (string) $line->id,
                'product_id' => (string) $line->product_id,
                'product_name' => (string) ($product?->name ?? ''),
                'ordered_quantity' => (float) $line->ordered_quantity,
                'received_quantity' => (float) $line->received_quantity,
                'unit_cost' => (float) $line->unit_cost_amount,
                'line_total' => (float) $line->line_total_amount,
                'currency' => (string) $line->currency,
            ];
        })->values();

        $suppliers = $isHardware
            ? HardwareSupplierModel::query()->where('shop_id', (int) $shopId)->where('status', 'active')->orderBy('name')->get()
            : PharmacySupplierModel::query()->where('shop_id', $shopId)->where('status', 'active')->orderBy('name')->get();

        return response()->json([
            'purchase_order' => [
                'id' => (string) $order->id,
                'status' => (string) $order->status,
                'supplier_id' => $order->supplier_id ? (string) $order->supplier_id : null,
                'supplier_name' => (string) ($order->supplier?->name ?? ''),
                'total_amount' => (float) $order->total_amount,
                'currency' => (string) $order->currency,
                'ordered_at' => $order->ordered_at?->format(DATE_ATOM),
                'expected_at' => $order->expected_at?->format(DATE_ATOM),
                'received_at' => $order->received_at?->format(DATE_ATOM),
                'created_at' => $order->created_at->format(DATE_ATOM),
            ],
            'lines' => $linesData,
            'suppliers' => $suppliers->map(fn ($s) => [
                'id' => (string) $s->id,
                'name' => (string) $s->name,
                'phone' => (string) ($s->phone ?? ''),
            ])->values(),
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
                'ordered_at' => $order['ordered_at'] ?? null,
                'expected_at' => $order['expected_at'] ?? null,
                'received_at' => $order['received_at'] ?? null,
                'lines' => $lines,
            ],
        ], 200);
    }

    private function moduleFromRequest(Request $request): string
    {
        $path = $request->path();
        return str_contains($path, '/hardware/') || str_contains($path, 'hardware/')
            ? 'hardware'
            : 'pharmacy';
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

