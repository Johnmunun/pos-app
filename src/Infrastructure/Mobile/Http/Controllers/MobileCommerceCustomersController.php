<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MobileCommerceCustomersController
{
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenantId = $user->tenant_id;
        if (($tenantId === null || $tenantId === '') && $user->shop_id && Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::query()->find($user->shop_id);
            $tenantId = $shop?->tenant_id;
        }

        if ($tenantId === null || $tenantId === '') {
            return response()->json(['message' => 'Tenant not found for this user.'], 403);
        }

        $customers = Customer::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->limit(200)
            ->get(['id', 'full_name', 'phone', 'email'])
            ->map(fn (Customer $c) => [
                'id' => (string) $c->id,
                'full_name' => (string) $c->full_name,
                'phone' => (string) ($c->phone ?? ''),
                'email' => (string) ($c->email ?? ''),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ], 200);
    }
}

