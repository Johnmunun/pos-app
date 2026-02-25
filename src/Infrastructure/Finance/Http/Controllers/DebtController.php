<?php

namespace Src\Infrastructure\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Src\Application\Finance\UseCases\Debt\CreateDebtUseCase;
use Src\Application\Finance\UseCases\Debt\SettleDebtUseCase;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Shared\ValueObjects\Money;

/**
 * Contrôleur Finance - Dettes (clients / fournisseurs).
 * Aucune logique métier : validation, délégation aux Use Cases.
 */
class DebtController
{
    public function __construct(
        private CreateDebtUseCase $createDebtUseCase,
        private SettleDebtUseCase $settleDebtUseCase,
        private DebtRepositoryInterface $debtRepository
    ) {}

    private function getTenantId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $tenantId = $user->tenant_id ?? $user->shop_id ?? null;
        if (!$tenantId) {
            abort(403, 'Tenant ID not found.');
        }
        return (string) $tenantId;
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if (!$shopId) {
            abort(403, 'Shop ID not found.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'party_id' => $request->input('party_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ]);
        $result = $this->debtRepository->findByTenantPaginated($tenantId, $perPage, $page, $filters);
        $items = array_map(fn ($d) => [
            'id' => $d->getId(),
            'type' => $d->getType(),
            'party_id' => $d->getPartyId(),
            'total_amount' => $d->getTotalAmount()->getAmount(),
            'paid_amount' => $d->getPaidAmount()->getAmount(),
            'currency' => $d->getCurrency(),
            'balance' => $d->getBalance()->getAmount(),
            'status' => $d->getStatus(),
            'due_date' => $d->getDueDate()?->format('Y-m-d'),
            'settled_at' => $d->getSettledAt()?->format('Y-m-d H:i:s'),
            'reference_type' => $d->getReferenceType(),
            'reference_id' => $d->getReferenceId(),
        ], $result['items']);
        return response()->json(['data' => $items, 'total' => $result['total']]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $debt = $this->debtRepository->findById($id);
        if (!$debt || $debt->getTenantId() !== $tenantId) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $debt->getId(),
                'type' => $debt->getType(),
                'party_id' => $debt->getPartyId(),
                'total_amount' => $debt->getTotalAmount()->getAmount(),
                'paid_amount' => $debt->getPaidAmount()->getAmount(),
                'currency' => $debt->getCurrency(),
                'balance' => $debt->getBalance()->getAmount(),
                'status' => $debt->getStatus(),
                'due_date' => $debt->getDueDate()?->format('Y-m-d'),
                'settled_at' => $debt->getSettledAt()?->format('Y-m-d H:i:s'),
                'reference_type' => $debt->getReferenceType(),
                'reference_id' => $debt->getReferenceId(),
                'can_close' => $debt->canClose(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:client,supplier',
            'party_id' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'reference_type' => 'required|string|max:64',
            'reference_id' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);
        $tenantId = $this->getTenantId($request);
        $shopId = $this->getShopId($request);
        $paidAmount = (float) ($validated['paid_amount'] ?? 0);
        $totalAmount = new Money((float) $validated['total_amount'], $validated['currency']);
        $paidMoney = new Money($paidAmount, $validated['currency']);
        $dueDate = !empty($validated['due_date'])
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $validated['due_date']) ?: null
            : null;
        $debt = $this->createDebtUseCase->execute(
            $tenantId,
            $shopId,
            $validated['type'],
            $validated['party_id'],
            $totalAmount,
            $paidMoney,
            $validated['reference_type'],
            $validated['reference_id'] ?? null,
            $dueDate
        );
        return response()->json([
            'data' => [
                'id' => $debt->getId(),
                'type' => $debt->getType(),
                'balance' => $debt->getBalance()->getAmount(),
                'status' => $debt->getStatus(),
            ],
        ], 201);
    }

    public function settle(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'nullable|string|max:64',
            'reference' => 'nullable|string|max:255',
        ]);
        $debt = $this->debtRepository->findById($id);
        if (!$debt) {
            abort(404);
        }
        if ($debt->getTenantId() !== $this->getTenantId($request)) {
            abort(403);
        }
        $settlement = $this->settleDebtUseCase->execute(
            $id,
            (float) $validated['amount'],
            $validated['currency'],
            (int) $request->user()->id,
            $validated['payment_method'] ?? null,
            $validated['reference'] ?? null
        );
        return response()->json([
            'data' => [
                'id' => $settlement->getId(),
                'debt_id' => $settlement->getDebtId(),
                'amount' => $settlement->getAmount()->getAmount(),
                'paid_at' => $settlement->getPaidAt()->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
