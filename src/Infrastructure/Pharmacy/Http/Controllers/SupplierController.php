<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Pharmacy\DTO\CreateSupplierDTO;
use Src\Application\Pharmacy\DTO\UpdateSupplierDTO;
use Src\Application\Pharmacy\UseCases\Supplier\ActivateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\CreateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\DeactivateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\UpdateSupplierUseCase;
use Src\Infrastructure\Pharmacy\Models\SupplierModel;
use Src\Infrastructure\Pharmacy\Models\SupplierProductPriceModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;

/**
 * Controller: SupplierController
 *
 * Gère les requêtes HTTP pour le module Fournisseurs.
 * Utilisable depuis Pharmacy ou Hardware selon le préfixe de route.
 */
class SupplierController extends Controller
{
    private function getModule(): string
    {
        $prefix = request()->route()?->getPrefix();
        return $prefix === 'hardware' ? 'Hardware' : 'Pharmacy';
    }

    public function __construct(
        private readonly CreateSupplierUseCase $createSupplierUseCase,
        private readonly UpdateSupplierUseCase $updateSupplierUseCase,
        private readonly ActivateSupplierUseCase $activateSupplierUseCase,
        private readonly DeactivateSupplierUseCase $deactivateSupplierUseCase
    ) {
    }

    /**
     * Affiche la liste des fournisseurs.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        // Paramètres de filtrage
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $perPage = (int) $request->input('per_page', 15);

        // Query builder
        /** @var \Illuminate\Database\Eloquent\Builder<SupplierModel> $query */
        $query = SupplierModel::query();

        if (!$isRoot) {
            $query->where('shop_id', $shopId);
        }

        // Appliquer les filtres
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Pagination
        $suppliers = $query->orderBy('name')
            ->paginate($perPage)
            ->through(fn ($supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'status' => $supplier->status,
                'total_orders' => $supplier->total_orders,
                'created_at' => $supplier->created_at->format('d/m/Y'),
            ]);

        return Inertia::render($this->getModule() . '/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Crée un nouveau fournisseur.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        try {
            $dto = new CreateSupplierDTO(
                shopId: (int) $shopId,
                name: $validated['name'],
                contactPerson: $validated['contact_person'] ?? null,
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null
            );

            $supplier = $this->createSupplierUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur créé avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'name' => $supplier->getName(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur création fournisseur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'shopId' => $shopId,
                'name' => $validated['name'] ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du fournisseur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche les détails d'un fournisseur.
     */
    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        /** @var SupplierModel|null $supplierModel */
        $supplierModel = SupplierModel::query()
            ->with(['purchaseOrders' => function ($q) {
                $q->orderByDesc('created_at')->limit(10);
            }])
            ->find($id);

        if (!$supplierModel) {
            abort(404, 'Fournisseur introuvable.');
        }

        // Vérifier l'accès
        if (!$isRoot && $supplierModel->shop_id !== $shopId) {
            abort(403, 'Accès non autorisé.');
        }

        // Formater les commandes récentes
        $recentOrders = [];
        $purchaseOrders = $supplierModel->purchaseOrders;
        $totalOrders = $purchaseOrders->count();
        foreach ($purchaseOrders as $po) {
            /** @var object $po */
            $recentOrders[] = [
                'id' => $po->id ?? '',
                'reference' => $po->reference ?? "PO-" . ($po->id ?? ''),
                'status' => $po->status ?? '',
                'total_amount' => $po->total_amount ?? 0,
                'created_at' => isset($po->created_at) && $po->created_at ? $po->created_at->format('d/m/Y') : '',
            ];
        }

        $supplier = [
            'id' => $supplierModel->id,
            'name' => $supplierModel->name,
            'contact_person' => $supplierModel->contact_person,
            'phone' => $supplierModel->phone,
            'email' => $supplierModel->email,
            'address' => $supplierModel->address,
            'status' => $supplierModel->status,
            'total_orders' => $totalOrders,
            'created_at' => $supplierModel->created_at->format('d/m/Y H:i'),
            'updated_at' => $supplierModel->updated_at->format('d/m/Y H:i'),
            'recent_orders' => $recentOrders,
        ];

        // Récupérer les prix fournisseur pour ce fournisseur
        $supplierPrices = SupplierProductPriceModel::query()
            ->where('supplier_id', $id)
            ->where('is_active', true)
            ->with('product:id,name,code,price_amount,price_currency')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                /** @var ProductModel|null $product */
                $product = $p->product;
                return [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'product_name' => $product?->name ?? '',
                    'product_code' => $product?->code ?? '',
                    'product_price' => $product?->price_amount ?? 0,
                    'normal_price' => (float) $p->normal_price,
                    'agreed_price' => $p->agreed_price !== null ? (float) $p->agreed_price : null,
                    'effective_price' => (float) $p->effective_price,
                    'tax_rate' => (float) ($p->tax_rate ?? 0),
                    'price_with_tax' => (float) ($p->price_with_tax ?? $p->effective_price),
                    'effective_from' => $p->effective_from?->format('Y-m-d'),
                ];
            })
            ->toArray();

        // Récupérer les produits pour le drawer d'ajout de prix
        $products = ProductModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price_amount', 'price_currency'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'price' => (float) $p->price_amount,
                'currency' => $p->price_currency,
            ])
            ->toArray();

        return Inertia::render($this->getModule() . '/Suppliers/Show', [
            'supplier' => $supplier,
            'supplierPrices' => $supplierPrices,
            'products' => $products,
        ]);
    }

    /**
     * Met à jour un fournisseur.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        try {
            $dto = new UpdateSupplierDTO(
                id: $id,
                name: $validated['name'],
                contactPerson: $validated['contact_person'] ?? null,
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null
            );

            $supplier = $this->updateSupplierUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur mis à jour avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'name' => $supplier->getName(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du fournisseur.',
            ], 500);
        }
    }

    /**
     * Active un fournisseur.
     */
    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $supplier = $this->activateSupplierUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur activé avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'status' => $supplier->getStatus()->getValue(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation du fournisseur.',
            ], 500);
        }
    }

    /**
     * Désactive un fournisseur.
     */
    public function deactivate(Request $request, string $id): JsonResponse
    {
        try {
            $supplier = $this->deactivateSupplierUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur désactivé avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'status' => $supplier->getStatus()->getValue(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du fournisseur.',
            ], 500);
        }
    }

    /**
     * Retourne la liste des fournisseurs actifs (pour les selects/dropdowns).
     */
    public function listActive(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        $suppliers = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'contact_person', 'phone', 'email']);

        return response()->json([
            'success' => true,
            'suppliers' => $suppliers,
        ]);
    }
}
