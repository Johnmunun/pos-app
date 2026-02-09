<?php

namespace Src\Domains\User\UseCases;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Use Case: StopImpersonationUseCase
 *
 * Arrêter l'impersonation et revenir au compte original.
 *
 * @package Src\Domains\User\UseCases
 */
class StopImpersonationUseCase
{
    /**
     * Arrêter l'impersonation
     *
     * @throws \Exception Si aucune impersonation en cours
     */
    public function execute(): void
    {
        if (!Session::has('impersonate.original_user_id')) {
            throw new \Exception('No impersonation in progress');
        }

        $originalUserId = Session::get('impersonate.original_user_id');
        $originalUser = UserModel::findOrFail($originalUserId);

        // Se reconnecter comme l'utilisateur original
        Auth::login($originalUser);

        // Nettoyer la session
        Session::forget('impersonate.original_user_id');
        Session::forget('impersonate.impersonating');
    }
}
