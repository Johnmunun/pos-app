<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Billing\Models\MerchantWalletBalance;
use Src\Infrastructure\Billing\Models\MerchantWithdrawalRequest;
use Src\Infrastructure\Billing\Services\MerchantWalletService;

class MerchantWithdrawalController extends Controller
{
    public function __construct(
        private readonly MerchantWalletService $merchantWalletService,
    ) {
    }

    public function page(Request $request): Response
    {
        $user = $request->user();
        if (!$this->canAccessWithdrawals($user)) {
            abort(403, 'Acces refuse.');
        }

        return Inertia::render('Billing/Withdrawals');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessWithdrawals($user)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

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
        if (!$this->canAccessWithdrawals($user)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
            'requested_amount' => ['required', 'numeric', 'gt:0'],
            'destination_type' => ['required', 'string', 'in:mobile_money,bank,wallet,paypal'],
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

    private function canAccessWithdrawals(mixed $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission('module.ecommerce')
            || $user->hasPermission('module.commerce')
            || $user->hasPermission('module.pharmacy')
            || $user->hasPermission('module.hardware')
            || $user->hasPermission('finance.dashboard.view')
            || $user->hasPermission('finance.report.view')
            || $user->hasPermission('referral.view')
            || $user->hasPermission('referral.stats.view')
            || $user->hasPermission('ecommerce.payment.view')
            || $user->hasPermission('ecommerce.order.view')
            || $user->hasPermission('ecommerce.order.payment.update')
            || $user->isRoot();
    }
}
