<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class AccessManagerController extends Controller
{
    public function roles(Request $request)
    {
        $search = $request->input('search', '');
        
        $query = Role::with('permissions')
            ->withCount(['users', 'permissions']);
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $roles = $query->get();
        
        // Récupérer toutes les permissions groupées par module pour le formulaire
        $allPermissions = Permission::where('is_old', false)
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('.', $permission->code);
                return $parts[0] ?? 'general';
            });
        
        return Inertia::render('Admin/AccessManager/Roles', [
            'roles' => $roles,
            'search' => $search,
            'allPermissions' => $allPermissions,
        ]);
    }
    
    public function getRole($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        $isRootRole = $role->name === 'ROOT' && $role->tenant_id === null;
        
        return response()->json([
            'role' => $role,
            'isRootRole' => $isRootRole,
        ]);
    }
    
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        
        // Assigner les permissions si fournies
        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->permissions()->sync($request->permissions);
        }
        
        return redirect()->route('admin.access.roles')
            ->with('success', 'Rôle créé avec succès');
    }
    
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $role = Role::findOrFail($id);
        
        // Ne pas modifier le nom du rôle ROOT
        if ($role->name === 'ROOT' && $role->tenant_id === null) {
            $role->update([
                'description' => $request->description,
            ]);
        } else {
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        }
        
        // Assigner les permissions si fournies
        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->permissions()->sync($request->permissions);
        }
        
        return redirect()->route('admin.access.roles')
            ->with('success', 'Rôle mis à jour avec succès');
    }
    
    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        
        // Ne pas supprimer le rôle ROOT
        if ($role->name === 'ROOT' && $role->tenant_id === null) {
            return redirect()->route('admin.access.roles')
                ->with('error', 'Le rôle ROOT ne peut pas être supprimé');
        }
        
        $role->delete();
        
        return redirect()->route('admin.access.roles')
            ->with('success', 'Rôle supprimé avec succès');
    }
    
    public function permissions()
    {
        $permissions = Permission::where('is_old', false)
            ->withCount('roles')
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('.', $permission->code);
                return $parts[0] ?? 'general';
            });
        
        return Inertia::render('Admin/AccessManager/Permissions', [
            'permissions' => $permissions,
        ]);
    }
    
    public function deletePermission($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        
        return response()->json(['message' => 'Permission deleted successfully']);
    }
    
    public function syncPermissions(Request $request)
    {
        // Si c'est une requête pour synchroniser depuis le YAML
        if ($request->isMethod('post') && $request->route()->named('admin.access.permissions.sync')) {
            $syncService = new \Src\Domains\User\Services\PermissionsSyncService();
            $report = $syncService->syncFromYaml();
            
            if (empty($report['errors'])) {
                $message = 'Permissions synchronisées avec succès. ' . $report['created'] . ' créées, ' . $report['updated'] . ' mises à jour, ' . $report['deleted'] . ' marquées comme obsolètes.';
                return Redirect::back()->with('message', $message);
            } else {
                return Redirect::back()->with('error', 'Erreur lors de la synchronisation: ' . implode(', ', $report['errors']));
            }
        }
        
        // Ancien comportement pour assigner des permissions à un rôle
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $role = Role::findOrFail($request->role_id);
        
        DB::table('role_permission')
            ->where('role_id', $request->role_id)
            ->delete();
            
        foreach ($request->permissions as $permissionId) {
            DB::table('role_permission')->insert([
                'role_id' => $request->role_id,
                'permission_id' => $permissionId,
            ]);
        }
        
        return response()->json(['message' => 'Permissions assignées avec succès']);
    }
}
