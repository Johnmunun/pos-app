<?php

namespace Src\Domains\User\UseCases;

use Domains\User\Repositories\UserRepository;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Use Case: ImpersonateUserUseCase
 *
 * Permet à un admin/ROOT de se connecter comme un autre utilisateur.
 * Nécessite la permission users.impersonate.
 *
 * @package Src\Domains\User\UseCases
 */
class ImpersonateUserUseCase
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Impersonner un utilisateur
     *
     * @param int $targetUserId ID de l'utilisateur à impersonner
     * @throws \Exception Si l'utilisateur n'existe pas ou est ROOT
     */
    public function execute(int $targetUserId): void
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            throw new \Exception('User must be authenticated to impersonate');
        }

        // Vérifier la permission (ROOT a toutes les permissions)
        if (!$currentUser->isRoot() && !$currentUser->hasPermission('users.impersonate')) {
            throw new \Exception('Permission denied: users.impersonate required');
        }

        $targetUser = UserModel::findOrFail($targetUserId);

        // Protection: ne pas impersonner ROOT
        if ($targetUser->isRoot()) {
            throw new \Exception('Cannot impersonate ROOT user');
        }

        // Vérifier que l'utilisateur cible est actif
        if ($targetUser->status !== 'active') {
            throw new \Exception('Cannot impersonate inactive user');
        }

        // Sauvegarder l'ID de l'utilisateur original
        Session::put('impersonate.original_user_id', $currentUser->id);
        Session::put('impersonate.impersonating', true);

        // Se connecter comme l'utilisateur cible
        Auth::login($targetUser);
    }
}
