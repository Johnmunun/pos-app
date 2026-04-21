<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel;

class SupplierController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $suppliers = SupplierModel::byShop($shopId)->orderBy('name')->get();
        $list = $suppliers->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'email' => $s->email,
            'phone' => $s->phone,
            'address' => $s->address,
            'is_active' => $s->is_active,
        ])->values()->all();

        return Inertia::render('Ecommerce/Suppliers/Index', [
            'suppliers' => $list,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $this->featureLimitService->assertCanCreateSupplier((string) ($request->user()?->tenant_id ?? ''));
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        SupplierModel::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Fournisseur créé.']);
        }

        return redirect()->route('ecommerce.suppliers.index')->with('success', 'Fournisseur créé.');
    }

    public function update(Request $request, string $id): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $supplier = SupplierModel::byShop($shopId)->find($id);
        if (!$supplier) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Fournisseur introuvable.'], 404);
            }
            return redirect()->route('ecommerce.suppliers.index')->with('error', 'Fournisseur introuvable.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : $supplier->is_active,
        ]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Fournisseur mis à jour.']);
        }

        return redirect()->route('ecommerce.suppliers.index')->with('success', 'Fournisseur mis à jour.');
    }

    public function toggleActive(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $supplier = SupplierModel::byShop($shopId)->find($id);
        if (!$supplier) {
            return redirect()->route('ecommerce.suppliers.index')->with('error', 'Fournisseur introuvable.');
        }

        $supplier->update(['is_active' => !$supplier->is_active]);
        $label = $supplier->is_active ? 'activé' : 'désactivé';

        return redirect()->route('ecommerce.suppliers.index')->with('success', "Fournisseur {$label}.");
    }
}
