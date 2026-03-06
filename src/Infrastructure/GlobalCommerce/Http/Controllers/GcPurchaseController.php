<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;
use Src\Application\GlobalCommerce\Procurement\DTO\CreatePurchaseDTO;
use Src\Application\GlobalCommerce\Procurement\UseCases\CreatePurchaseUseCase;
use Src\Application\GlobalCommerce\Procurement\UseCases\ReceivePurchaseUseCase;
use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel;

class GcPurchaseController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $depotId = $request->session()->get('current_depot_id');

        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', (int) $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }

        if ($user->tenant_id) {
            return (string) $user->tenant_id;
        }

        abort(403, 'Shop ID not found.');
    }

    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository,
        private ProductRepositoryInterface $productRepository,
        private CreatePurchaseUseCase $createPurchaseUseCase,
        private ReceivePurchaseUseCase $receivePurchaseUseCase
    ) {
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $models = PurchaseModel::with(['lines', 'supplier'])
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->offset(0)
            ->get();

        $list = $models->map(fn ($m) => [
            'id' => $m->id,
            'supplier_id' => $m->supplier_id,
            'supplier_name' => $m->supplier?->name ?? '—',
            'status' => $m->status,
            'total_amount' => (float) $m->total_amount,
            'currency' => $m->currency,
            'expected_at' => $m->expected_at?->format('Y-m-d'),
            'received_at' => $m->received_at?->format('d/m/Y H:i'),
            'created_at' => $m->created_at->format('d/m/Y H:i'),
            'lines_count' => $m->lines->count(),
        ])->values()->all();

        return Inertia::render('Commerce/Purchases/Index', [
            'purchases' => $list,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $suppliers = SupplierModel::byShop($shopId)->where('is_active', true)->orderBy('name')->get();
        $products = $this->productRepository->search($shopId, '', ['is_active' => true]);
        return Inertia::render('Commerce/Purchases/Create', [
            'suppliers' => $suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'products' => array_map(fn ($p) => [
                'id' => $p->getId(),
                'sku' => $p->getSku(),
                'name' => $p->getName(),
                'currency' => $p->getPurchasePrice()->getCurrency(),
            ], $products),
            'currency' => $products[0]->getPurchasePrice()->getCurrency() ?? 'USD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:gc_suppliers,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|uuid|exists:gc_products,id',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_cost' => 'required|numeric|min:0',
            'expected_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $lines = array_values(array_filter($validated['lines'], fn ($l) => ((float) ($l['quantity'] ?? 0)) > 0 && ((float) ($l['unit_cost'] ?? 0)) >= 0));
        if (empty($lines)) {
            return redirect()->back()->withErrors(['lines' => 'Au moins une ligne avec quantité et coût unitaire.'])->withInput();
        }
        $dto = new CreatePurchaseDTO(
            $shopId,
            $validated['supplier_id'],
            array_map(fn ($l) => [
                'product_id' => $l['product_id'],
                'quantity' => (float) $l['quantity'],
                'unit_cost' => (float) $l['unit_cost'],
            ], $lines),
            $request->input('currency', 'USD'),
            $validated['expected_at'] ?? null,
            $validated['notes'] ?? null
        );
        try {
            $this->createPurchaseUseCase->execute($dto);
            return redirect()->route('commerce.purchases.index')->with('success', 'Bon de commande créé.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $purchase = $this->purchaseRepository->findById($id);
        if (!$purchase || $purchase->getShopId() !== $shopId) {
            return redirect()->route('commerce.purchases.index')->with('error', 'Bon de commande introuvable.');
        }
        $supplier = SupplierModel::find($purchase->getSupplierId());
        $lines = array_map(fn ($l) => [
            'product_name' => $l['product_name'],
            'ordered_quantity' => $l['ordered_quantity'],
            'received_quantity' => $l['received_quantity'],
            'unit_cost' => $l['unit_cost'],
            'line_total' => $l['line_total'],
        ], $purchase->getLines());
        return Inertia::render('Commerce/Purchases/Show', [
            'purchase' => [
                'id' => $purchase->getId(),
                'supplier_name' => $supplier?->name ?? '—',
                'status' => $purchase->getStatus(),
                'total_amount' => $purchase->getTotalAmount(),
                'currency' => $purchase->getCurrency(),
                'expected_at' => $purchase->getExpectedAt()?->format('Y-m-d'),
                'received_at' => $purchase->getReceivedAt()?->format('d/m/Y H:i'),
                'notes' => $purchase->getNotes(),
                'created_at' => $purchase->getCreatedAt()->format('d/m/Y H:i'),
                'lines' => $lines,
            ],
        ]);
    }

    public function receive(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user && !$user->hasPermission('commerce.purchases.receive') && $user->type !== 'ROOT') {
            abort(403, 'Vous n\'avez pas le droit de réceptionner un bon de commande.');
        }
        $shopId = $this->getShopId($request);
        try {
            $this->receivePurchaseUseCase->execute($shopId, $id);
            return redirect()->route('commerce.purchases.show', $id)->with('success', 'Bon réceptionné. Stock mis à jour.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
