<?php

namespace Src\Domains\User\UseCases;

use Domains\User\Repositories\UserRepository;
use Domains\User\ValueObjects\UserStatus;
use App\Models\User as UserModel;

/**
 * Use Case: UpdateUserStatusUseCase
 *
 * Mettre à jour le statut d'un utilisateur (active, blocked, suspended, pending).
 * Protection: ROOT ne peut pas être modifié.
 *
 * @package Src\Domains\User\UseCases
 */
class UpdateUserStatusUseCase
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Mettre à jour le statut d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $status Nouveau statut (pending, active, blocked, suspended)
     * @throws \Exception Si l'utilisateur est ROOT ou si le statut est invalide
     */
    public function execute(int $userId, string $status): void
    {
        $userModel = UserModel::findOrFail($userId);

        // Protection: ne pas modifier ROOT
        if ($userModel->isRoot()) {
            throw new \Exception('Cannot modify status of ROOT user');
        }

        // Valider le statut
        $userStatus = new UserStatus($status);

        // Mettre à jour
        $userModel->update(['status' => $userStatus->getValue()]);
    }
}
