<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Billing\Models\MerchantWithdrawalRequest;
use Src\Infrastructure\Billing\Services\MerchantWalletService;

class MerchantWithdrawalAdminController extends Controller
{
    public function __construct(
        private readonly MerchantWalletService $merchantWalletService,
    ) {
    }

    public function dashboard(Request $request): Response
    {
        $status = strtolower((string) $request->query('status', 'pending'));
        if (!in_array($status, $this->allowedStatuses(), true)) {
            $status = 'pending';
        }

        return Inertia::render('Admin/BillingWithdrawals', [
            'initialStatus' => $status,
            'initialData' => $this->buildListPayload($status),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('admin.billing.manage')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $status = strtolower((string) $request->query('status', 'pending'));
        if (!in_array($status, $this->allowedStatuses(), true)) {
            return response()->json(['message' => 'Statut invalide.'], 422);
        }

        return response()->json($this->buildListPayload($status));
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('admin.billing.manage')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        try {
            $withdrawal = $this->merchantWalletService->approveWithdrawalRequest($id, $user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Demande approuvee.',
            'withdrawal' => $withdrawal,
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('admin.billing.manage')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $withdrawal = $this->merchantWalletService->rejectWithdrawalRequest(
                $id,
                $user,
                (string) ($validated['reason'] ?? '')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Demande rejetee et fonds debloques.',
            'withdrawal' => $withdrawal,
        ]);
    }

    public function markPaid(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('admin.billing.manage')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'transfer_reference' => ['nullable', 'string', 'max:190'],
            'provider' => ['nullable', 'string', 'max:100'],
        ]);

        $meta = [
            'transfer_reference' => (string) ($validated['transfer_reference'] ?? ''),
            'provider' => (string) ($validated['provider'] ?? 'manual'),
        ];

        try {
            $withdrawal = $this->merchantWalletService->completeWithdrawalRequest($id, $user, $meta);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Retrait marque comme paye.',
            'withdrawal' => $withdrawal,
        ]);
    }

    public function markFailed(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('admin.billing.manage')) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'transfer_reference' => ['nullable', 'string', 'max:190'],
            'provider' => ['nullable', 'string', 'max:100'],
        ]);

        $meta = [
            'transfer_reference' => (string) ($validated['transfer_reference'] ?? ''),
            'provider' => (string) ($validated['provider'] ?? 'manual'),
        ];

        try {
            $withdrawal = $this->merchantWalletService->failWithdrawalRequest(
                $id,
                $user,
                (string) ($validated['reason'] ?? ''),
                $meta
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Retrait marque en echec et fonds debloques.',
            'withdrawal' => $withdrawal,
        ]);
    }

    private function buildListPayload(string $status): array
    {
        $query = MerchantWithdrawalRequest::query()
            ->leftJoin('tenants', 'tenants.id', '=', 'merchant_withdrawal_requests.tenant_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'merchant_withdrawal_requests.user_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'merchant_withdrawal_requests.approved_by_user_id')
            ->orderByDesc('merchant_withdrawal_requests.id')
            ->select([
                'merchant_withdrawal_requests.*',
                DB::raw('COALESCE(tenants.name, merchant_withdrawal_requests.tenant_id) as tenant_name'),
                DB::raw('COALESCE(requester.name, "Utilisateur inconnu") as requester_name'),
                DB::raw('approver.name as approver_name'),
            ]);

        if ($status !== 'all') {
            $query->where('merchant_withdrawal_requests.status', $status);
        }

        $items = $query->limit(200)->get();

        $statsRows = MerchantWithdrawalRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $stats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'paid' => 0,
            'failed' => 0,
            'all' => 0,
        ];

        foreach ($statsRows as $row) {
            $key = (string) ($row->status ?? '');
            $count = (int) ($row->total ?? 0);
            if (array_key_exists($key, $stats)) {
                $stats[$key] = $count;
            }
            $stats['all'] += $count;
        }

        return [
            'items' => $items,
            'status' => $status,
            'stats' => $stats,
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedStatuses(): array
    {
        return ['pending', 'approved', 'rejected', 'paid', 'failed', 'all'];
    }
}
