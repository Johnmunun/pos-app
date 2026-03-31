<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Customer;
use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\SaleLineModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as HardwareProductModel;

class MobilePharmacySalesController
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
        $saleType = (string) $request->input('sale_type', '');
        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        $limit = max(1, min(200, (int) $request->input('limit', 50)));
        $offset = max(0, (int) $request->input('offset', 0));

        $query = SaleModel::query()
            ->where('shop_id', $shopId)
            ->with(['creator:id,name', 'customer:id,full_name'])
            ->orderByDesc('created_at');

        if ($status !== '' && in_array($status, ['DRAFT', 'COMPLETED', 'CANCELLED'], true)) {
            $query->where('status', $status);
        }

        if ($saleType !== '' && in_array($saleType, ['retail', 'wholesale'], true)) {
            $query->where('sale_type', $saleType);
        }

        // Pharmacy depot filtering: selected depot + central.
        if ($depotId !== null && $depotId > 0) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id', $depotId)
                    ->orWhereNull('depot_id');
            });
        }

        $module = $this->moduleFromRequest($request);
        $viewAllPermission = $module === 'hardware' ? 'hardware.sales.view.all' : 'pharmacy.sales.view.all';
        $userModel = UserModel::query()->find($user->id);
        $canViewAllSales = $userModel && ($userModel->isRoot() || $userModel->hasPermission($viewAllPermission));
        if (!$canViewAllSales) {
            $query->where('created_by', (int) $user->id);
        }

        $salesRows = $query->offset($offset)->limit($limit + 1)->get();
        $hasMore = $salesRows->count() > $limit;
        $sales = $salesRows->take($limit)->map(function (SaleModel $sale) {
            $customer = Customer::query()->find($sale->customer_id);
            $creator = UserModel::query()->find($sale->created_by);
            return [
                'id' => (string) $sale->id,
                'status' => (string) $sale->status,
                'sale_type' => (string) ($sale->sale_type ?? 'retail'),
                'total_amount' => (float) $sale->total_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance_amount' => (float) $sale->balance_amount,
                'currency' => (string) $sale->currency,
                'customer_id' => $sale->customer_id ? (string) $sale->customer_id : null,
                'customer_name' => $customer?->full_name,
                'seller_name' => $creator?->name,
                'depot_id' => $sale->depot_id,
                'created_at' => $sale->created_at->format(DATE_ATOM),
                'completed_at' => $sale->completed_at?->format(DATE_ATOM),
            ];
        })->values();

        return response()->json([
            'sales' => $sales,
            'filters' => [
                'status' => $status,
                'sale_type' => $saleType,
                'depot_id' => $depotId,
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
            ->with(['creator:id,name', 'customer:id,full_name,phone,email'])
            ->first();

        if ($sale === null) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        $module = $this->moduleFromRequest($request);
        $viewAllPermission = $module === 'hardware' ? 'hardware.sales.view.all' : 'pharmacy.sales.view.all';
        $userModel = UserModel::query()->find($user->id);
        $canViewAllSales = $userModel && ($userModel->isRoot() || $userModel->hasPermission($viewAllPermission));
        if (!$canViewAllSales && (int) $sale->created_by !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $lines = SaleLineModel::query()
            ->where('sale_id', $sale->id)
            ->get()
            ->map(function (SaleLineModel $line) use ($module) {
                $product = $module === 'hardware'
                    ? HardwareProductModel::query()->find($line->product_id)
                    : ProductModel::query()->find($line->product_id);
                return [
                    'id' => (string) $line->id,
                    'product_id' => (string) $line->product_id,
                    'product_name' => $product?->name,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price_amount,
                    'line_total' => (float) $line->line_total_amount,
                    'currency' => (string) $line->currency,
                    'discount_percent' => $line->discount_percent !== null ? (float) $line->discount_percent : null,
                ];
            })
            ->values();

        $customer = $sale->customer_id ? Customer::query()->find($sale->customer_id) : null;
        $creator = UserModel::query()->find($sale->created_by);

        return response()->json([
            'sale' => [
                'id' => (string) $sale->id,
                'status' => (string) $sale->status,
                'sale_type' => (string) ($sale->sale_type ?? 'retail'),
                'total_amount' => (float) $sale->total_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance_amount' => (float) $sale->balance_amount,
                'currency' => (string) $sale->currency,
                'depot_id' => $sale->depot_id,
                'seller_name' => $creator?->name,
                'created_at' => $sale->created_at->format(DATE_ATOM),
                'completed_at' => $sale->completed_at?->format(DATE_ATOM),
            ],
            'customer' => $customer ? [
                'id' => (string) $customer->id,
                'full_name' => (string) $customer->full_name,
                'phone' => (string) ($customer->phone ?? ''),
                'email' => (string) ($customer->email ?? ''),
            ] : null,
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
        $customer = $payload['customer'] ?? null;
        $lines = $payload['lines'] ?? [];

        return response()->json([
            'receipt' => [
                'sale_id' => (string) ($sale['id'] ?? ''),
                'created_at' => $sale['created_at'] ?? null,
                'completed_at' => $sale['completed_at'] ?? null,
                'status' => $sale['status'] ?? null,
                'currency' => $sale['currency'] ?? null,
                'total_amount' => (float) ($sale['total_amount'] ?? 0),
                'paid_amount' => (float) ($sale['paid_amount'] ?? 0),
                'balance_amount' => (float) ($sale['balance_amount'] ?? 0),
                'seller_name' => $sale['seller_name'] ?? null,
                'customer' => $customer,
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

