<?php

namespace Src\Infrastructure\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Domains\User\UseCases\AssignUserRoleUseCase;
use Src\Domains\User\UseCases\UpdateUserStatusUseCase;
use Src\Domains\User\UseCases\ResetUserPasswordUseCase;
use Src\Domains\User\UseCases\DeleteUserUseCase;
use Src\Domains\User\UseCases\ImpersonateUserUseCase;
use Src\Domains\User\UseCases\StopImpersonationUseCase;

/**
 * Controller: UserManagementController
 *
 * Gestion complète des utilisateurs pour admin/ROOT.
 * Toutes les actions sont protégées par permissions.
 *
 * @package Src\Infrastructure\User\Http\Controllers
 */
class UserManagementController extends Controller
{
    public function __construct(
        private readonly AssignUserRoleUseCase $assignUserRoleUseCase,
        private readonly UpdateUserStatusUseCase $updateUserStatusUseCase,
        private readonly ResetUserPasswordUseCase $resetUserPasswordUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
        private readonly ImpersonateUserUseCase $impersonateUserUseCase,
        private readonly StopImpersonationUseCase $stopImpersonationUseCase
    ) {
    }

    /**
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(Request $request, int $userId)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $user = Auth::user();

        // Vérifier la permission (ROOT bypass)
        if (!$user->isRoot() && !$user->hasPermission('users.assign_role')) {
            abort(403, 'Permission denied: users.assign_role required');
        }

        try {
            $this->assignUserRoleUseCase->execute(
                userId: $userId,
                roleId: $request->role_id,
                tenantId: $request->tenant_id
            );

            // Recharger l'utilisateur pour avoir les permissions à jour
            $assignedUser = \App\Models\User::findOrFail($userId);
            $assignedUser->load('roles.permissions');
            
            return response()->json([
                'message' => 'Rôle assigné avec succès. L\'utilisateur devra se reconnecter pour voir les nouvelles permissions.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mettre à jour le statut d'un utilisateur
     */
    public function updateStatus(Request $request, int $userId)
    {
        $request->validate([
            'status' => 'required|in:pending,active,blocked,suspended',
        ]);

        $user = Auth::user();

        // Vérifier la permission selon le statut
        $permission = match($request->status) {
            'active' => 'users.activate',
            'blocked', 'suspended' => 'users.block',
            'pending' => 'users.activate',
            default => null,
        };

        if ($permission && !$user->isRoot() && !$user->hasPermission($permission)) {
            abort(403, "Permission denied: {$permission} required");
        }

        try {
            $this->updateUserStatusUseCase->execute(
                userId: $userId,
                status: $request->status
            );

            return response()->json([
                'message' => 'User status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(Request $request, int $userId)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Vérifier la permission (ROOT bypass)
        if (!$user->isRoot() && !$user->hasPermission('users.reset_password')) {
            abort(403, 'Permission denied: users.reset_password required');
        }

        try {
            $this->resetUserPasswordUseCase->execute(
                userId: $userId,
                newPassword: $request->password
            );

            return response()->json([
                'message' => 'Password reset successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete(int $userId)
    {
        $user = Auth::user();

        // Vérifier la permission (ROOT bypass)
        if (!$user->isRoot() && !$user->hasPermission('users.delete')) {
            abort(403, 'Permission denied: users.delete required');
        }

        try {
            $this->deleteUserUseCase->execute(userId: $userId);

            return response()->json([
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Impersonner un utilisateur
     */
    public function impersonate(Request $request, int $userId)
    {
        $user = Auth::user();

        // Vérifier la permission (ROOT bypass)
        if (!$user->isRoot() && !$user->hasPermission('users.impersonate')) {
            abort(403, 'Permission denied: users.impersonate required');
        }

        try {
            $this->impersonateUserUseCase->execute(targetUserId: $userId);

            return redirect()->route('dashboard')->with('success', 'Impersonation démarrée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Arrêter l'impersonation
     */
    public function stopImpersonation()
    {
        try {
            $this->stopImpersonationUseCase->execute();

            // Rediriger vers le dashboard après l'arrêt de l'impersonation
            // L'utilisateur original (ROOT/Admin) sera maintenant connecté
            return redirect()->route('dashboard')->with('success', 'Impersonation arrêtée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
