<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Currency\Services\TenantDisplayCurrencyService;

class DisplayCurrencyController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        $tenantId = $user->tenant_id ?? $user->shop_id;
        if ($tenantId === null || (string) $tenantId === '') {
            return response()->json(['message' => 'Tenant introuvable.'], 422);
        }

        app(TenantDisplayCurrencyService::class)->remember(
            $request,
            (string) $tenantId,
            $validated['currency']
        );

        return response()->json(['ok' => true, 'currency' => strtoupper($validated['currency'])]);
    }
}
