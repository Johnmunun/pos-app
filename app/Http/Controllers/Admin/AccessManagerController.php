<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: AccessManagerController
 *
 * Gère le module Access Manager (RBAC)
 * - Gestion des rôles
 * - Gestion des permissions
 * - Synchronisation depuis permissions.yaml
 */
class AccessManagerController extends Controller
{
    protected PermissionSyncService $permissionSyncService;

    public function __construct(PermissionSyncService $permissionSyncService)
    {
        $this->permissionSyncService = $permissionSyncService;
    }

    /**
     * Afficher la page de gestion des rôles
     */
    public function roles(Request $request): Response
    {
        $search = $request->get('search', '');

        $query = Role::withCount(['users', 'permissions'])
            ->orderBy('created_at', 'desc');

        // Recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $roles = $query->get();

        // Récupérer toutes les permissions groupées pour le drawer
        $allPermissions = Permission::where('is_old', false)
            ->orderBy('group')
            ->orderBy('code')
            ->get()
            ->groupBy('group');

        return Inertia::render('Admin/AccessManager/Roles', [
            'roles' => $roles,
            'search' => $search,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Afficher la page de gestion des permissions
     */
    public function permissions(Request $request): Response
    {
        $search = $request->get('search', '');

        $query = Permission::withCount('roles')
            ->where('is_old', false)
            ->orderBy('group')
            ->orderBy('code');

        // Recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%");
            });
        }

        $permissions = $query->get()->groupBy('group');

        return Inertia::render('Admin/AccessManager/Permissions', [
            'permissions' => $permissions,
            'search' => $search,
        ]);
    }

    /**
     * Récupérer les données d'un rôle pour édition (API pour le drawer)
     */
    public function getRole(int $id): \Illuminate\Http\JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        // Vérifier si c'est le rôle ROOT
        $isRootRole = $role->name === 'ROOT' && $role->tenant_id === null;

        return response()->json([
            'role' => $role,
            'isRootRole' => $isRootRole,
        ]);
    }

    /**
     * Créer un nouveau rôle
     */
    public function createRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,NULL,id,tenant_id,' . ($request->tenant_id ?? 'NULL'),
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'tenant_id' => null, // Pour l'instant, tous les rôles sont globaux (ROOT)
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }
        });

        return redirect()->route('admin.access.roles')
            ->with('success', 'Rôle créé avec succès');
    }

    /**
     * Mettre à jour un rôle
     */
    public function updateRole(int $id, Request $request): RedirectResponse
    {
        $role = Role::findOrFail($id);

        // Empêcher la modification destructive du rôle ROOT
        $isRootRole = $role->name === 'ROOT' && $role->tenant_id === null;
        if ($isRootRole) {
            // Seule la description peut être modifiée
            $validated = $request->validate([
                'description' => 'nullable|string|max:500',
            ]);

            $role->update([
                'description' => $validated['description'] ?? $role->description,
            ]);

            return redirect()->route('admin.access.roles')
                ->with('success', 'Rôle ROOT mis à jour (seule la description a été modifiée)');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $id . ',id,tenant_id,' . ($role->tenant_id ?? 'NULL'),
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::transaction(function () use ($role, $validated) {
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }
        });

        return redirect()->route('admin.access.roles')
            ->with('success', 'Rôle mis à jour avec succès');
    }

    /**
     * Supprimer un rôle
     */
    public function deleteRole(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        // Empêcher la suppression du rôle ROOT
        if ($role->name === 'ROOT' && $role->tenant_id === null) {
            return redirect()->route('admin.access.roles')
                ->with('error', 'Le rôle ROOT ne peut pas être supprimé');
        }

        // Vérifier si le rôle est utilisé
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.access.roles')
                ->with('error', 'Ce rôle est assigné à des utilisateurs et ne peut pas être supprimé');
        }

        $role->delete();

        return redirect()->route('admin.access.roles')
            ->with('success', 'Rôle supprimé avec succès');
    }

    /**
     * Supprimer une permission
     */
    public function deletePermission(int $id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);

        // Vérifier si la permission est utilisée
        if ($permission->roles()->count() > 0) {
            return redirect()->route('admin.access.permissions')
                ->with('error', 'Cette permission est assignée à des rôles et ne peut pas être supprimée');
        }

        $permission->delete();

        return redirect()->route('admin.access.permissions')
            ->with('success', 'Permission supprimée avec succès');
    }

    /**
     * Synchroniser les permissions depuis permissions.yaml
     */
    public function syncPermissions(): RedirectResponse
    {
        $result = $this->permissionSyncService->syncFromDefaultFile('permissions.yaml');

        // Réassigner toutes les permissions au rôle ROOT
        $rootRole = Role::where('name', 'ROOT')->whereNull('tenant_id')->first();
        if ($rootRole) {
            $permissionIds = Permission::where('is_old', false)->pluck('id')->all();
            $rootRole->permissions()->sync($permissionIds);
        }

        return redirect()->route('admin.access.permissions')
            ->with('success', sprintf(
                'Permissions synchronisées : %d créées, %d mises à jour, %d marquées comme obsolètes',
                $result['created'],
                $result['updated'],
                $result['marked_old']
            ));
    }
}

