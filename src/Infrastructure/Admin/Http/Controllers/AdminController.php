<?php

namespace Src\Infrastructure\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Domains\Admin\UseCases\GetAllUsersUseCase;
use Src\Domains\Admin\UseCases\GetAllTenantsUseCase;
use Src\Domains\Admin\UseCases\ToggleUserStatusUseCase;
use Src\Domains\Admin\UseCases\ToggleTenantStatusUseCase;

class AdminController
{
    private GetAllUsersUseCase $getAllUsersUseCase;
    private GetAllTenantsUseCase $getAllTenantsUseCase;
    private ToggleUserStatusUseCase $toggleUserStatusUseCase;
    private ToggleTenantStatusUseCase $toggleTenantStatusUseCase;

    public function __construct(
        GetAllUsersUseCase $getAllUsersUseCase,
        GetAllTenantsUseCase $getAllTenantsUseCase,
        ToggleUserStatusUseCase $toggleUserStatusUseCase,
        ToggleTenantStatusUseCase $toggleTenantStatusUseCase
    ) {
        $this->getAllUsersUseCase = $getAllUsersUseCase;
        $this->getAllTenantsUseCase = $getAllTenantsUseCase;
        $this->toggleUserStatusUseCase = $toggleUserStatusUseCase;
        $this->toggleTenantStatusUseCase = $toggleTenantStatusUseCase;
    }

    /**
     * Display the tenant selection page.
     */
    public function selectTenant()
    {
        $tenants = $this->getAllTenantsUseCase->execute();

        return Inertia::render('Admin/Tenants/Select', [
            'tenants' => $tenants
        ]);
    }

    /**
     * Display the tenant dashboard.
     */
    public function tenantDashboard($id)
    {
        // For now, we'll just render the view with the ID
        return Inertia::render('Admin/Tenants/Dashboard', [
            'tenantId' => $id
        ]);
    }

    /**
     * Manage tenants page.
     */
    public function manageTenants()
    {
        $tenants = $this->getAllTenantsUseCase->execute();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants
        ]);
    }

    /**
     * Manage users page.
     */
    public function manageUsers()
    {
        $users = $this->getAllUsersUseCase->execute();
        
        // Récupérer tous les rôles disponibles
        $roles = \App\Models\Role::active()->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Toggle tenant status.
     */
    public function toggleTenant(Request $request, $id)
    {
        $this->toggleTenantStatusUseCase->execute($id);

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Toggle user status.
     */
    public function toggleUser(Request $request, $id)
    {
        $this->toggleUserStatusUseCase->execute($id);

        return response()->json(['message' => 'Status updated successfully']);
    }
}