<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Quincaillerie\DTO\CreateSupplierDTO;
use Src\Application\Quincaillerie\DTO\UpdateSupplierDTO;
use Src\Application\Quincaillerie\UseCases\Supplier\ActivateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\CreateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\DeactivateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\UpdateSupplierUseCase;
use Src\Infrastructure\Quincaillerie\Models\SupplierModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;
use Src\Application\Quincaillerie\Services\DepotFilterService;

/**
 * Controller: SupplierController
 *
 * Gère les requêtes HTTP pour le module Fournisseurs Quincaillerie.
 * Module complètement séparé de Pharmacy.
 */
class SupplierController extends Controller
{
    public function __construct(
        private readonly CreateSupplierUseCase $createSupplierUseCase,
        private readonly UpdateSupplierUseCase $updateSupplierUseCase,
        private readonly ActivateSupplierUseCase $activateSupplierUseCase,
        private readonly DeactivateSupplierUseCase $deactivateSupplierUseCase,
        private readonly DepotFilterService $depotFilterService
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

        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');

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
        /** @var \Illuminate\Pagination\LengthAwarePaginator<\Src\Infrastructure\Quincaillerie\Models\SupplierModel> $paginator */
        $paginator = $query->orderBy('name')->paginate($perPage);
        $suppliers = $paginator->through(function ($supplier) {
            /** @var \Src\Infrastructure\Quincaillerie\Models\SupplierModel $supplier */
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'status' => $supplier->status,
                'created_at' => $supplier->created_at->format('d/m/Y'),
            ];
        });

        return Inertia::render('Hardware/Suppliers/Index', [
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

            // Assigner le dépôt selon les permissions
            $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
            if ($effectiveDepotId !== null) {
                $supplierModel = SupplierModel::find($supplier->getId());
                if ($supplierModel) {
                    $supplierModel->update(['depot_id' => $effectiveDepotId]);
                }
            }

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
        $supplierModel = SupplierModel::query()->find($id);

        if (!$supplierModel) {
            abort(404, 'Fournisseur introuvable.');
        }

        // Vérifier l'accès
        if (!$isRoot && $supplierModel->shop_id !== $shopId) {
            abort(403, 'Accès non autorisé.');
        }

        $supplier = [
            'id' => $supplierModel->id,
            'name' => $supplierModel->name,
            'contact_person' => $supplierModel->contact_person,
            'phone' => $supplierModel->phone,
            'email' => $supplierModel->email,
            'address' => $supplierModel->address,
            'status' => $supplierModel->status,
            'created_at' => $supplierModel->created_at->format('d/m/Y H:i'),
            'updated_at' => $supplierModel->updated_at->format('d/m/Y H:i'),
        ];

        // Récupérer les produits pour le drawer d'ajout de prix (si nécessaire plus tard)
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

        return Inertia::render('Hardware/Suppliers/Show', [
            'supplier' => $supplier,
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
