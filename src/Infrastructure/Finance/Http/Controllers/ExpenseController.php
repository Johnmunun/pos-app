<?php

namespace Src\Infrastructure\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Finance\DTO\CreateExpenseDTO;
use Src\Application\Finance\UseCases\Expense\CreateExpenseUseCase;
use Src\Application\Finance\UseCases\Expense\ListExpensesUseCase;
use Src\Domain\Finance\Repositories\ExpenseRepositoryInterface;

/**
 * Contrôleur Finance - Dépenses.
 * Aucune logique métier : validation, délégation aux Use Cases, retour HTTP.
 */
class ExpenseController
{
    public function __construct(
        private ListExpensesUseCase $listExpensesUseCase,
        private CreateExpenseUseCase $createExpenseUseCase,
        private ExpenseRepositoryInterface $expenseRepository
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

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = max(1, (int) $request->input('page', 1));
        $filtersInput = [
            'shop_id' => $request->input('shop_id'),
            'category' => $request->input('category'),
            'status' => $request->input('status'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];
        $filters = array_filter($filtersInput);
        $result = $this->listExpensesUseCase->execute($tenantId, $perPage, $page, $filters);
        $items = array_map(fn ($e) => [
            'id' => $e->getId(),
            'tenant_id' => $e->getTenantId(),
            'shop_id' => $e->getShopId(),
            'amount' => $e->getAmount()->getAmount(),
            'currency' => $e->getAmount()->getCurrency(),
            'category' => $e->getCategory()->getValue(),
            'description' => $e->getDescription(),
            'supplier_id' => $e->getSupplierId(),
            'attachment_path' => $e->getAttachmentPath(),
            'status' => $e->getStatus(),
            'created_by' => $e->getCreatedBy(),
            'paid_at' => $e->getPaidAt()?->format('Y-m-d H:i:s'),
            'created_at' => $e->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $result['items']);

        $total = $result['total'];
        $lastPage = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        $from = $total === 0 ? 0 : (($page - 1) * $perPage + 1);
        $to = min($total, $page * $perPage);

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => $items,
            'filters' => $filtersInput,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_id' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'category' => 'required|string|in:stock_purchase,transport,salary,fixed_charge,utilities,maintenance,other',
            'description' => 'nullable|string|max:1000',
            'supplier_id' => 'nullable|string',
            'attachment_path' => 'nullable|string|max:500',
            'depot_id' => 'nullable|string',
        ]);
        $tenantId = $this->getTenantId($request);
        $shopId = $validated['shop_id'] ?? $this->getShopId($request);
        $dto = new CreateExpenseDTO(
            tenantId: $tenantId,
            shopId: $shopId,
            amount: (float) $validated['amount'],
            currency: $validated['currency'],
            category: $validated['category'],
            description: $validated['description'] ?? '',
            createdBy: (int) $request->user()->id,
            supplierId: $validated['supplier_id'] ?? null,
            attachmentPath: $validated['attachment_path'] ?? null,
            depotId: $validated['depot_id'] ?? null
        );
        $expense = $this->createExpenseUseCase->execute($dto);

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'Dépense enregistrée avec succès.');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $expense = $this->expenseRepository->findById($id);
        if (!$expense || $expense->getTenantId() !== $tenantId) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $expense->getId(),
                'tenant_id' => $expense->getTenantId(),
                'shop_id' => $expense->getShopId(),
                'amount' => $expense->getAmount()->getAmount(),
                'currency' => $expense->getAmount()->getCurrency(),
                'category' => $expense->getCategory()->getValue(),
                'description' => $expense->getDescription(),
                'supplier_id' => $expense->getSupplierId(),
                'attachment_path' => $expense->getAttachmentPath(),
                'status' => $expense->getStatus(),
                'created_by' => $expense->getCreatedBy(),
                'paid_at' => $expense->getPaidAt()?->format('Y-m-d H:i:s'),
                'created_at' => $expense->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $expense = $this->expenseRepository->findById($id);
        if (!$expense || $expense->getTenantId() !== $tenantId) {
            abort(404);
        }
        $this->expenseRepository->delete($id);
        return response()->json(null, 204);
    }
}
