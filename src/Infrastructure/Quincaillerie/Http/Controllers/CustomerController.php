<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Quincaillerie\DTO\CreateCustomerDTO;
use Src\Application\Quincaillerie\DTO\UpdateCustomerDTO;
use Src\Application\Quincaillerie\UseCases\Customer\CreateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\UpdateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\ActivateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\DeactivateCustomerUseCase;
use Src\Infrastructure\Quincaillerie\Models\CustomerModel;
use Src\Application\Quincaillerie\Services\DepotFilterService;

/**
 * Controller: CustomerController
 *
 * Gère les requêtes HTTP pour le module Clients Quincaillerie.
 * Module complètement séparé de Pharmacy.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CreateCustomerUseCase $createCustomerUseCase,
        private readonly UpdateCustomerUseCase $updateCustomerUseCase,
        private readonly ActivateCustomerUseCase $activateCustomerUseCase,
        private readonly DeactivateCustomerUseCase $deactivateCustomerUseCase,
        private readonly DepotFilterService $depotFilterService
    ) {
    }

    /**
     * Affiche la liste des clients.
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
        $customerType = $request->input('customer_type', '');
        $perPage = (int) $request->input('per_page', 15);

        /** @var \Illuminate\Database\Eloquent\Builder<CustomerModel> $query */
        $query = CustomerModel::query();

        if (!$isRoot) {
            $query->where('shop_id', $shopId);
        }

        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');

        // Appliquer les filtres
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($customerType)) {
            $query->where('customer_type', $customerType);
        }

        // Pagination
        /** @var \Illuminate\Pagination\LengthAwarePaginator<\Src\Infrastructure\Quincaillerie\Models\CustomerModel> $paginator */
        $paginator = $query->orderBy('name')->paginate($perPage);
        $customers = $paginator->through(function ($customer) {
            /** @var \Src\Infrastructure\Quincaillerie\Models\CustomerModel $customer */
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'customer_type' => $customer->customer_type,
                'customer_type_label' => $customer->customer_type === 'company' ? 'Entreprise' : 'Particulier',
                'tax_number' => $customer->tax_number,
                'credit_limit' => $customer->credit_limit,
                'status' => $customer->status,
                'created_at' => $customer->created_at->format('d/m/Y'),
            ];
        });

        return Inertia::render('Hardware/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'customer_type' => $customerType,
                'per_page' => $perPage,
            ],
            'routePrefix' => 'hardware',
        ]);
    }

    /**
     * Crée un nouveau client.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'customer_type' => 'nullable|string|in:individual,company',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        try {
            $dto = new CreateCustomerDTO(
                shopId: (int) $shopId,
                name: $validated['name'],
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null,
                customerType: $validated['customer_type'] ?? 'individual',
                taxNumber: $validated['tax_number'] ?? null,
                creditLimit: isset($validated['credit_limit']) ? (float) $validated['credit_limit'] : null
            );

            $customer = $this->createCustomerUseCase->execute($dto);

            // Assigner le dépôt selon les permissions
            $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
            if ($effectiveDepotId !== null) {
                $customerModel = CustomerModel::find($customer->getId());
                if ($customerModel) {
                    $customerModel->update(['depot_id' => $effectiveDepotId]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur création client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client.',
            ], 500);
        }
    }

    /**
     * Affiche les détails d'un client.
     */
    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        /** @var CustomerModel|null $customerModel */
        $customerModel = CustomerModel::query()->find($id);

        if (!$customerModel) {
            abort(404, 'Client introuvable.');
        }

        // Vérifier l'accès
        if (!$isRoot && $customerModel->shop_id !== $shopId) {
            abort(403, 'Accès non autorisé.');
        }

        $customer = [
            'id' => $customerModel->id,
            'name' => $customerModel->name,
            'phone' => $customerModel->phone,
            'email' => $customerModel->email,
            'address' => $customerModel->address,
            'customer_type' => $customerModel->customer_type,
            'customer_type_label' => $customerModel->customer_type === 'company' ? 'Entreprise' : 'Particulier',
            'tax_number' => $customerModel->tax_number,
            'credit_limit' => $customerModel->credit_limit,
            'status' => $customerModel->status,
            'created_at' => $customerModel->created_at->format('d/m/Y H:i'),
            'updated_at' => $customerModel->updated_at->format('d/m/Y H:i'),
        ];

        return Inertia::render('Hardware/Customers/Show', [
            'customer' => $customer,
            'routePrefix' => 'hardware',
        ]);
    }

    /**
     * Met à jour un client.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'customer_type' => 'nullable|string|in:individual,company',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        try {
            $dto = new UpdateCustomerDTO(
                id: $id,
                name: $validated['name'],
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null,
                customerType: $validated['customer_type'] ?? null,
                taxNumber: $validated['tax_number'] ?? null,
                creditLimit: isset($validated['credit_limit']) ? (float) $validated['credit_limit'] : null
            );

            $customer = $this->updateCustomerUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
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
                'message' => 'Erreur lors de la mise à jour du client.',
            ], 500);
        }
    }

    /**
     * Active un client.
     */
    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->activateCustomerUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Client activé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'status' => $customer->getStatus(),
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
                'message' => 'Erreur lors de l\'activation du client.',
            ], 500);
        }
    }

    /**
     * Désactive un client.
     */
    public function deactivate(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->deactivateCustomerUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Client désactivé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'status' => $customer->getStatus(),
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
                'message' => 'Erreur lors de la désactivation du client.',
            ], 500);
        }
    }

    /**
     * Retourne la liste des clients actifs (pour les selects/dropdowns).
     */
    public function listActive(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        $customers = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'customer_type']);

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }
}
