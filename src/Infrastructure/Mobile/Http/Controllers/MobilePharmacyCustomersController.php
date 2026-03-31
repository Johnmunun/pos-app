<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Pharmacy\Models\CustomerModel;

class MobilePharmacyCustomersController
{
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $shopId = $this->resolveShopId($user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;

        $query = CustomerModel::query()
            ->where('status', 'active');

        // Non-root users should see only their shop customers.
        if (!$user->isRoot()) {
            $query->where('shop_id', $shopId);
        }

        // Pharmacy POS: depot filter -> current depot + central (depot_id = null)
        if ($depotId !== null && $depotId > 0) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id', $depotId)
                    ->orWhereNull('depot_id');
            });
        }

        $customers = $query
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'phone',
                'email',
                'customer_type',
                'credit_limit',
            ])
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

    private function resolveShopId(UserModel $user): ?string
    {
        return $user->shop_id !== null
            ? (string) $user->shop_id
            : ($user->tenant_id ? (string) $user->tenant_id : null);
    }
}

