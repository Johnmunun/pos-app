<?php

namespace Src\Infrastructure\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Src\Application\Finance\UseCases\Invoice\CreateInvoiceFromSaleUseCase;
use Src\Domain\Finance\Entities\Invoice;
use Src\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use Src\Shared\ValueObjects\Money;

/**
 * Contrôleur Finance - Factures.
 * Aucune logique métier : validation, délégation aux Use Cases.
 */
class InvoiceController
{
    public function __construct(
        private CreateInvoiceFromSaleUseCase $createInvoiceFromSaleUseCase,
        private InvoiceRepositoryInterface $invoiceRepository
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
            'status' => $request->input('status'),
            'source_type' => $request->input('source_type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ]);
        $result = $this->invoiceRepository->findByTenantPaginated($tenantId, $perPage, $page, $filters);
        $items = array_map(fn ($inv) => [
            'id' => $inv->getId(),
            'number' => $inv->getNumber(),
            'source_type' => $inv->getSourceType(),
            'source_id' => $inv->getSourceId(),
            'total_amount' => $inv->getTotalAmount()->getAmount(),
            'paid_amount' => $inv->getPaidAmount()->getAmount(),
            'currency' => $inv->getCurrency(),
            'status' => $inv->getStatus(),
            'issued_at' => $inv->getIssuedAt()->format('Y-m-d H:i:s'),
            'validated_at' => $inv->getValidatedAt()?->format('Y-m-d H:i:s'),
            'paid_at' => $inv->getPaidAt()?->format('Y-m-d H:i:s'),
        ], $result['items']);
        return response()->json(['data' => $items, 'total' => $result['total']]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $invoice = $this->invoiceRepository->findById($id);
        if (!$invoice || $invoice->getTenantId() !== $tenantId) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $invoice->getId(),
                'number' => $invoice->getNumber(),
                'source_type' => $invoice->getSourceType(),
                'source_id' => $invoice->getSourceId(),
                'total_amount' => $invoice->getTotalAmount()->getAmount(),
                'paid_amount' => $invoice->getPaidAmount()->getAmount(),
                'currency' => $invoice->getCurrency(),
                'status' => $invoice->getStatus(),
                'issued_at' => $invoice->getIssuedAt()->format('Y-m-d H:i:s'),
                'validated_at' => $invoice->getValidatedAt()?->format('Y-m-d H:i:s'),
                'paid_at' => $invoice->getPaidAt()?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /** Créer une facture à partir d'une vente (liaison Caisse). */
    public function createFromSale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:draft,validated,paid',
        ]);
        $tenantId = $this->getTenantId($request);
        $shopId = $this->getShopId($request);
        $total = new Money((float) $validated['total_amount'], $request->input('currency', 'CDF'));
        $paid = new Money((float) ($validated['paid_amount'] ?? 0), $request->input('currency', 'CDF'));
        $status = $validated['status'] ?? Invoice::STATUS_DRAFT;
        $invoice = $this->createInvoiceFromSaleUseCase->execute(
            $tenantId,
            $shopId,
            $validated['sale_id'],
            $total,
            $paid,
            $status
        );
        return response()->json([
            'data' => [
                'id' => $invoice->getId(),
                'number' => $invoice->getNumber(),
                'source_type' => $invoice->getSourceType(),
                'source_id' => $invoice->getSourceId(),
                'status' => $invoice->getStatus(),
            ],
        ], 201);
    }
}
