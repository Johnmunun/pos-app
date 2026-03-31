<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Quincaillerie\Models\CustomerModel;

class MobileHardwareCustomersController
{
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $shopId = $this->resolveShopId($request, $user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;

        $query = CustomerModel::query()->where('status', 'active');
        if (!$user->isRoot()) {
            $query->where('shop_id', (int) $shopId);
        }

        // Hardware depot filtering: selected depot + central.
        if ($depotId !== null && $depotId > 0) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id', $depotId)
                    ->orWhereNull('depot_id');
            });
        }

        $customers = $query
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'customer_type', 'credit_limit'])
            ->map(function (CustomerModel $c) {
                return [
                    'id' => (string) $c->id,
                    'name' => (string) $c->name,
                    'phone' => (string) ($c->phone ?? ''),
                    'email' => (string) ($c->email ?? ''),
                    'customer_type' => (string) ($c->customer_type ?? 'individual'),
                    'credit_limit' => $c->credit_limit !== null ? (float) $c->credit_limit : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'customers' => $customers,
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

