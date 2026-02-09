<?php

namespace Src\Domains\User\UseCases;

use Domains\User\Repositories\UserRepository;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Hash;

/**
 * Use Case: ResetUserPasswordUseCase
 *
 * Réinitialiser le mot de passe d'un utilisateur.
 * Admin/ROOT peut forcer un reset sans email.
 *
 * @package Src\Domains\User\UseCases
 */
class ResetUserPasswordUseCase
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $newPassword Nouveau mot de passe en clair
     * @throws \Exception Si l'utilisateur n'existe pas
     */
    public function execute(int $userId, string $newPassword): void
    {
        $userModel = UserModel::findOrFail($userId);

        // Protection: ne pas modifier ROOT (optionnel, selon les besoins)
        // if ($userModel->type === 'ROOT') {
        //     throw new \Exception('Cannot reset password for ROOT user');
        // }

        // Valider le mot de passe (minimum 8 caractères)
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        // Mettre à jour le mot de passe
        $userModel->update([
            'password' => Hash::make($newPassword),
        ]);
    }
}
