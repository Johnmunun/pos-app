<?php

namespace Src\Infrastructure\Loyalty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Loyalty\Services\LoyaltyService;

class LoyaltyController
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {
    }

    private function tenantId(Request $request): int
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403);
        }

        return (int) $user->tenant_id;
    }

    public function lookup(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $code = (string) $request->query('code', '');
        $account = $this->loyaltyService->lookupByCode($tenantId, $code);
        if ($account === null) {
            return response()->json(['message' => 'Carte fidélité introuvable.'], 404);
        }

        return response()->json(['account' => $account]);
    }

    public function account(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'module' => 'required|string|in:commerce,pharmacy,hardware',
            'customer_id' => 'required|string',
            'customer_name' => 'nullable|string|max:255',
        ]);

        $account = $this->loyaltyService->resolveAccount(
            $tenantId,
            $validated['module'],
            $validated['customer_id'],
            true
        );

        if ($account === null) {
            return response()->json(['enabled' => false]);
        }

        return response()->json([
            'account' => $this->loyaltyService->formatAccountPayload(
                $account,
                $validated['customer_name'] ?? null
            ),
        ]);
    }

    public function history(Request $request, string $accountId): JsonResponse
    {
        $this->tenantId($request);

        return response()->json([
            'transactions' => $this->loyaltyService->getTransactionHistory($accountId),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'module' => 'required|string|in:commerce,pharmacy,hardware',
            'customer_id' => 'required|string',
            'points' => 'required|integer|min:0',
            'sale_subtotal' => 'required|numeric|min:0',
        ]);

        return response()->json(
            $this->loyaltyService->previewRedemption(
                $tenantId,
                $validated['module'],
                $validated['customer_id'],
                (int) $validated['points'],
                (float) $validated['sale_subtotal']
            )
        );
    }
}
