<?php

namespace Src\Domains\User\UseCases;

use Domains\User\Repositories\UserRepository;
use App\Models\User as UserModel;

/**
 * Use Case: DeleteUserUseCase
 *
 * Supprimer un utilisateur (soft delete).
 * Protection: ROOT ne peut pas être supprimé.
 *
 * @package Src\Domains\User\UseCases
 */
class DeleteUserUseCase
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Supprimer un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @throws \Exception Si l'utilisateur est ROOT ou n'existe pas
     */
    public function execute(int $userId): void
    {
        $userModel = UserModel::findOrFail($userId);

        // Protection: ne pas supprimer ROOT
        if ($userModel->isRoot()) {
            throw new \Exception('Cannot delete ROOT user');
        }

        // Soft delete
        $userModel->delete();
    }
}
