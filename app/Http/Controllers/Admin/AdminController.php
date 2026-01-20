<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: AdminController
 *
 * Gère les pages d'administration ROOT
 * - Sélection de tenant
 * - Dashboard admin
 * - Gestion des tenants
 * - Gestion des utilisateurs
 */
class AdminController extends Controller
{
    /**
     * Afficher la page de sélection de tenant
     */
    public function selectTenant(): Response
    {
        // Récupérer tous les tenants avec le count d'utilisateurs
        $tenants = DB::table('tenants')
            ->leftJoin('users', 'tenants.id', '=', 'users.tenant_id')
            ->select('tenants.*', DB::raw('count(users.id) as users_count'))
            ->groupBy('tenants.id')
            ->orderBy('tenants.created_at', 'desc')
            ->get();

        return Inertia::render('Admin/SelectTenant', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Dashboard d'un tenant spécifique pour le ROOT
     */
    public function tenantDashboard(int $tenantId): Response | RedirectResponse
    {
        // Vérifier que le tenant existe
        $tenant = DB::table('tenants')->where('id', $tenantId)->first();

        if (!$tenant) {
            return redirect(route('admin.tenants.select.view'))->with('error', 'Tenant not found');
        }

        // Récupérer les statistiques du tenant
        $stats = [
            'users_count' => DB::table('users')->where('tenant_id', $tenantId)->count(),
            'active_users' => DB::table('users')->where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'last_login' => DB::table('users')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('last_login_at')
                ->latest('last_login_at')
                ->first(),
        ];

        // Récupérer les utilisateurs du tenant
        $users = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/TenantDashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    /**
     * Page de gestion des tenants
     */
    public function manageTenants(): Response
    {
        $tenants = DB::table('tenants')
            ->leftJoin('users', 'tenants.id', '=', 'users.tenant_id')
            ->select('tenants.*', DB::raw('count(users.id) as users_count'))
            ->groupBy('tenants.id')
            ->orderBy('tenants.created_at', 'desc')
            ->get();

        return Inertia::render('Admin/ManageTenants', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Page de gestion des utilisateurs globaux
     */
    public function manageUsers(): Response
    {
        $users = DB::table('users')
            ->leftJoin('tenants', 'users.tenant_id', '=', 'tenants.id')
            ->select(
                'users.*',
                'tenants.name as tenant_name'
            )
            ->orderBy('users.created_at', 'desc')
            ->get();

        return Inertia::render('Admin/ManageUsers', [
            'users' => $users,
        ]);
    }

    /**
     * Activer/Désactiver un tenant
     */
    public function toggleTenant(int $tenantId, Request $request): RedirectResponse
    {
        $tenant = DB::table('tenants')->where('id', $tenantId)->first();

        if (!$tenant) {
            return redirect()->back()->with('error', 'Tenant not found');
        }

        DB::table('tenants')
            ->where('id', $tenantId)
            ->update(['is_active' => !$tenant->is_active]);

        $status = !$tenant->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Tenant {$status} successfully");
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleUser(int $userId, Request $request): RedirectResponse
    {
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        }

        // Empêcher de désactiver le ROOT user
        if ($user->type === 'ROOT') {
            return redirect()->back()->with('error', 'Cannot disable ROOT user');
        }

        DB::table('users')
            ->where('id', $userId)
            ->update(['is_active' => !$user->is_active]);

        $status = !$user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "User {$status} successfully");
    }
}
