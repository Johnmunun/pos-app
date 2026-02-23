<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\Depot;
use App\Models\Sale;
use Src\Application\Pharmacy\DTO\CreateSellerDTO;
use Src\Application\Pharmacy\UseCases\Seller\CreateSellerUseCase;
use Src\Domains\User\UseCases\AssignUserRoleUseCase;
use Src\Domains\User\UseCases\ImpersonateSellerUseCase;
use Src\Domains\User\Services\ModulePermissionService;
use Illuminate\Support\Facades\Hash;

/**
 * Controller: SellerController
 *
 * Gère la création et la gestion des vendeurs pour une pharmacie.
 * Seuls les TENANT_ADMIN et MERCHANT peuvent créer des vendeurs.
 */
class SellerController extends Controller
{
    public function __construct(
        private readonly CreateSellerUseCase $createSellerUseCase,
        private readonly AssignUserRoleUseCase $assignRoleUseCase,
        private readonly ImpersonateSellerUseCase $impersonateSellerUseCase,
        private readonly ModulePermissionService $modulePermissionService
    ) {
    }

    /**
     * Afficher la liste des vendeurs
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Non authentifié.');
        }
        /** @var int|null $tenantId */
        $tenantId = $user->tenant_id;

        if ($tenantId === null || $tenantId === 0) {
            abort(403, 'Vous devez être associé à un tenant pour voir les vendeurs.');
        }
        /** @var int $tenantId */
        
        // Récupérer le secteur d'activité du tenant
        $tenant = Tenant::find($tenantId);
        $sector = $tenant?->sector;

        // Récupérer tous les vendeurs du tenant
        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $sellersCollection */
        $sellersCollection = User::where('tenant_id', $tenantId)
            ->where('type', 'SELLER')
            ->with(['roles' => function ($query) use ($tenantId) {
                $query->wherePivot('tenant_id', $tenantId)
                      ->orWherePivotNull('tenant_id');
            }])
            ->with('depots')
            ->withCount('roles')
            ->orderBy('created_at', 'desc')
            ->get();

        $sellers = $sellersCollection->map(function ($seller) {
                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'email' => $seller->email,
                    'status' => $seller->status ?? ($seller->is_active ? 'active' : 'pending'),
                    'roles' => $seller->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ])->values(),
                    'depots' => $seller->depots->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code]),
                    'depot_ids' => $seller->depots->pluck('id')->values()->toArray(),
                    'roles_count' => $seller->roles_count,
                    'created_at' => $seller->created_at?->format('d/m/Y H:i'),
                ];
            });

        // Rôles assignables : du tenant OU rôles globaux (créés par ROOT, tenant_id null)
        // Sécurité : uniquement ceux dont toutes les permissions sont du secteur
        /** @var \Illuminate\Database\Eloquent\Collection<int, Role> $rolesCollection */
        $rolesCollection = Role::where(function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
        })
            ->with('permissions')
            ->withCount('permissions')
            ->get();
        
        $authorizedPermissions = $this->modulePermissionService->getAuthorizedPermissions($sector);
        
        $availableRoles = $rolesCollection->filter(function ($role) use ($authorizedPermissions) {
            $rolePermissions = $role->permissions->pluck('code')->toArray();
            if (empty($rolePermissions)) {
                return false;
            }
            foreach ($rolePermissions as $perm) {
                if (!in_array($perm, $authorizedPermissions)) {
                    return false;
                }
            }
            return true;
        })->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions_count' => $role->permissions_count,
                'permissions' => $role->permissions->pluck('code')->toArray(),
                'is_global' => $role->tenant_id === null,
            ];
        })->values();

        // Stats vendeurs
        $total = $sellersCollection->count();
        $active = $sellersCollection->where('status', 'active')->count();
        $blocked = $sellersCollection->where('status', 'blocked')->count();
        $stats = [
            'total' => $total,
            'active' => $active,
            'blocked' => $blocked,
            'pending' => max(0, $total - $active - $blocked),
        ];

        // Nombre de ventes par vendeur (optionnel)
        // Dépôts du tenant (pour affectation vendeurs)
        $availableDepots = Depot::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])
            ->values();

        $sellerIds = $sellersCollection->pluck('id')->toArray();
        $salesBySeller = Sale::where('tenant_id', $tenantId)
            ->whereIn('seller_id', $sellerIds)
            ->selectRaw('seller_id, COUNT(*) as count')
            ->groupBy('seller_id')
            ->pluck('count', 'seller_id');

        $sellersWithSales = $sellers->map(function ($sellerItem) use ($salesBySeller) {
            $sellerItem['sales_count'] = $salesBySeller[$sellerItem['id']] ?? 0;
            return $sellerItem;
        });

        return Inertia::render('Pharmacy/Sellers/Index', [
            'sellers' => $sellersWithSales,
            'availableRoles' => $availableRoles,
            'availableDepots' => $availableDepots,
            'tenantSector' => $sector,
            'stats' => $stats,
        ]);
    }

    /**
     * Créer un nouveau vendeur
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Non authentifié.');
        }
        /** @var int|null $tenantId */
        $tenantId = $user->tenant_id;

        if ($tenantId === null || $tenantId === 0) {
            return redirect()->back()->with('error', 'Vous devez être associé à un tenant pour créer un vendeur.');
        }
        /** @var int $tenantId */
        
        // Récupérer le secteur d'activité du tenant
        $tenant = Tenant::find($tenantId);
        $sector = $tenant?->sector;

        // Vérifier les permissions
        if (!$user->hasPermission('pharmacy.seller.create')) {
            abort(403, 'Vous n\'avez pas la permission de créer des vendeurs.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'depot_ids' => 'nullable|array',
            'depot_ids.*' => 'exists:depots,id',
            'role_ids.*' => [
                'exists:roles,id',
                /**
                 * @param string $attribute
                 * @param mixed $value
                 * @param callable(string): void $fail
                 */
                function ($attribute, $value, $fail) use ($tenantId) {
                    $role = Role::with('permissions')->find($value);
                    if (!$role) {
                        $fail('Rôle invalide.');
                        return;
                    }
                    // Accepter : rôle du tenant OU rôle global (tenant_id null) avec permissions secteur uniquement
                    $tenant = Tenant::find($tenantId);
                    $sector = $tenant?->sector;
                    $modulePermissionService = app(\Src\Domains\User\Services\ModulePermissionService::class);
                    $authorizedPermissions = $modulePermissionService->getAuthorizedPermissions($sector);
                    $rolePermissions = $role->permissions->pluck('code')->toArray();
                    if (empty($rolePermissions)) {
                        $fail('Ce rôle n\'a aucune permission.');
                        return;
                    }
                    foreach ($rolePermissions as $perm) {
                        if (!in_array($perm, $authorizedPermissions)) {
                            $sectorLabel = match($sector) {
                                'pharmacy' => 'Pharmacie',
                                'butchery' => 'Boucherie',
                                'kiosk' => 'Kiosque',
                                'supermarket' => 'Supermarché',
                                'hardware' => 'Quincaillerie',
                                default => 'votre secteur',
                            };
                            $fail("Le rôle contient des permissions non autorisées pour le secteur {$sectorLabel}.");
                            return;
                        }
                    }
                    if ($role->tenant_id !== null && $role->tenant_id != $tenantId) {
                        $fail('Le rôle sélectionné n\'appartient pas à votre boutique.');
                    }
                },
            ],
            'is_active' => 'boolean',
        ]);

        try {
            $dto = new CreateSellerDTO(
                tenantId: $tenantId,
                firstName: $validated['first_name'],
                lastName: $validated['last_name'],
                email: $validated['email'],
                password: $validated['password'],
                roleIds: $validated['role_ids'] ?? null,
                isActive: $validated['is_active'] ?? true
            );

            $seller = $this->createSellerUseCase->execute($dto);

            // Affecter les dépôts au vendeur
            $depotIds = $validated['depot_ids'] ?? [];
            $validDepotIds = Depot::where('tenant_id', $tenantId)
                ->whereIn('id', $depotIds)
                ->pluck('id')
                ->toArray();
            $seller->depots()->sync($validDepotIds);

            return redirect()->route('pharmacy.sellers.index')
                ->with('success', 'Vendeur créé avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to create seller', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la création du vendeur.'])
                ->withInput();
        }
    }

    /**
     * Mettre à jour un vendeur
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Non authentifié.');
        }
        /** @var int|null $tenantId */
        $tenantId = $user->tenant_id;

        if ($tenantId === null || $tenantId === 0) {
            abort(403, 'Vous devez être associé à un tenant.');
        }
        /** @var int $tenantId */
        
        // Récupérer le secteur d'activité du tenant
        $tenant = Tenant::find($tenantId);
        $sector = $tenant?->sector;
        
        $seller = User::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('type', 'SELLER')
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'role_ids' => 'sometimes|nullable|array',
            'depot_ids' => 'sometimes|nullable|array',
            'depot_ids.*' => 'exists:depots,id',
            'role_ids.*' => [
                'exists:roles,id',
                /**
                 * @param string $attribute
                 * @param mixed $value
                 * @param callable(string): void $fail
                 */
                function ($attribute, $value, $fail) use ($tenantId) {
                    $role = Role::with('permissions')->find($value);
                    if (!$role) {
                        $fail('Rôle invalide.');
                        return;
                    }
                    // Accepter : rôle du tenant OU rôle global (tenant_id null) avec permissions secteur uniquement
                    $tenant = Tenant::find($tenantId);
                    $sector = $tenant?->sector;
                    $modulePermissionService = app(\Src\Domains\User\Services\ModulePermissionService::class);
                    $authorizedPermissions = $modulePermissionService->getAuthorizedPermissions($sector);
                    $rolePermissions = $role->permissions->pluck('code')->toArray();
                    if (empty($rolePermissions)) {
                        $fail('Ce rôle n\'a aucune permission.');
                        return;
                    }
                    foreach ($rolePermissions as $perm) {
                        if (!in_array($perm, $authorizedPermissions)) {
                            $sectorLabel = match($sector) {
                                'pharmacy' => 'Pharmacie',
                                'butchery' => 'Boucherie',
                                'kiosk' => 'Kiosque',
                                'supermarket' => 'Supermarché',
                                'hardware' => 'Quincaillerie',
                                default => 'votre secteur',
                            };
                            $fail("Le rôle contient des permissions non autorisées pour le secteur {$sectorLabel}.");
                            return;
                        }
                    }
                    if ($role->tenant_id !== null && $role->tenant_id != $tenantId) {
                        $fail('Le rôle sélectionné n\'appartient pas à votre boutique.');
                    }
                },
            ],
            'status' => 'sometimes|in:active,pending,blocked',
        ]);

        DB::beginTransaction();
        try {
            // Mettre à jour les informations de base
            $updateData = [];
            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $nameParts = explode(' ', $seller->name, 2);
                $firstName = $validated['first_name'] ?? $nameParts[0];
                $lastName = $validated['last_name'] ?? ($nameParts[1] ?? '');
                $updateData['name'] = trim($firstName . ' ' . $lastName);
            }

            if (isset($validated['email'])) {
                $updateData['email'] = $validated['email'];
            }

            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
                $updateData['is_active'] = $validated['status'] === 'active';
            }

            if (!empty($updateData)) {
                $seller->update($updateData);
            }

            // Mettre à jour les rôles si fournis
            if (isset($validated['role_ids'])) {
                $authorizedPermissions = $this->modulePermissionService->getAuthorizedPermissions($sector);
                $validRoleIds = [];
                foreach ($validated['role_ids'] as $roleId) {
                    $role = Role::where('id', $roleId)
                        ->where(function ($q) use ($tenantId) {
                            $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                        })
                        ->with('permissions')
                        ->first();
                    if (!$role) {
                        continue;
                    }
                    $rolePermissions = $role->permissions->pluck('code')->toArray();
                    if (empty($rolePermissions)) {
                        continue;
                    }
                    $isValid = true;
                    foreach ($rolePermissions as $perm) {
                        if (!in_array($perm, $authorizedPermissions)) {
                            $isValid = false;
                            break;
                        }
                    }
                    if ($isValid) {
                        $validRoleIds[] = $roleId;
                    }
                }
                
                // Remplacer les rôles du vendeur pour ce tenant par les rôles valides
                if (!empty($validRoleIds)) {
                    $this->assignRoleUseCase->assignRolesForTenant(
                        userId: $seller->id,
                        roleIds: $validRoleIds,
                        tenantId: $tenantId
                    );
                } else {
                    DB::table('user_role')
                        ->where('user_id', $seller->id)
                        ->where('tenant_id', $tenantId)
                        ->delete();
                }
            }

            // Mettre à jour les dépôts si fournis
            if (array_key_exists('depot_ids', $validated)) {
                $depotIds = $validated['depot_ids'] ?? [];
                $depotIds = is_array($depotIds) ? array_map('intval', array_filter($depotIds)) : [];
                $validDepotIds = [];
                if (!empty($depotIds)) {
                    $validDepotIds = Depot::where('tenant_id', $tenantId)
                        ->whereIn('id', $depotIds)
                        ->pluck('id')
                        ->toArray();
                }
                $seller->depots()->sync($validDepotIds);
            }

            DB::commit();

            return redirect()->route('pharmacy.sellers.index')
                ->with('success', 'Vendeur mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update seller', [
                'seller_id' => $id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = config('app.debug')
                ? $e->getMessage()
                : 'Une erreur est survenue lors de la mise à jour.';

            return redirect()->back()
                ->withErrors(['error' => $errorMessage])
                ->withInput();
        }
    }

    /**
     * Supprimer un vendeur
     *
     * @param int|string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = request()->user();
        if ($user === null) {
            abort(401, 'Non authentifié.');
        }
        /** @var int|null $tenantId */
        $tenantId = $user->tenant_id;

        if ($tenantId === null || $tenantId === 0) {
            abort(403, 'Vous devez être associé à un tenant.');
        }
        /** @var int $tenantId */

        $seller = User::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('type', 'SELLER')
            ->firstOrFail();

        // Vérifier les permissions
        if (!$user->hasPermission('pharmacy.seller.delete')) {
            abort(403, 'Vous n\'avez pas la permission de supprimer des vendeurs.');
        }

        // Ne pas supprimer physiquement, juste désactiver
        $seller->update([
            'status' => 'blocked',
            'is_active' => false,
        ]);

        return redirect()->route('pharmacy.sellers.index')
            ->with('success', 'Vendeur désactivé avec succès.');
    }

    /**
     * Impersonner un vendeur du tenant
     *
     * @param int|string $id ID du vendeur
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function impersonate($id)
    {
        $user = request()->user();
        if ($user === null) {
            abort(401, 'Non authentifié.');
        }

        try {
            $this->impersonateSellerUseCase->execute((int) $id);
            // Rediriger vers la caisse (ventes) : les vendeurs ont pharmacy.sales.view|manage
            // Le dashboard requiert module.pharmacy que certains vendeurs n'ont pas
            $redirectUrl = route('pharmacy.sales.index');
            if (request()->wantsJson()) {
                return response()->json(['redirect' => $redirectUrl]);
            }
            return redirect($redirectUrl)
                ->with('success', 'Impersonation démarrée avec succès');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
