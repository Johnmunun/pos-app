<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Billing\Models\MerchantWalletBalance;
use Src\Infrastructure\Billing\Models\MerchantWithdrawalRequest;
use Src\Infrastructure\Billing\Services\MerchantWalletService;

class MerchantWithdrawalController extends Controller
{
    public function __construct(
        private readonly MerchantWalletService $merchantWalletService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = (string) ($user?->tenant_id ?? '');
        if ($tenantId === '') {
            return response()->json(['message' => 'Tenant introuvable.'], 422);
        }

        $balances = MerchantWalletBalance::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('currency_code')
            ->get(['currency_code', 'available_balance', 'pending_balance', 'locked_balance']);

        $requests = MerchantWithdrawalRequest::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'balances' => $balances,
            'withdrawals' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
            'requested_amount' => ['required', 'numeric', 'gt:0'],
            'destination_type' => ['required', 'string', 'in:mobile_money,bank,wallet'],
            'destination_reference' => ['required', 'string', 'max:190'],
        ]);

        try {
            $withdrawal = $this->merchantWalletService->createWithdrawalRequest($user, $validated);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Demande de retrait enregistrée.',
            'withdrawal' => $withdrawal,
        ]);
    }
}
