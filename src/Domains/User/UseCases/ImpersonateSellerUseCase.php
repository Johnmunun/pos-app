<?php

namespace Src\Domains\User\UseCases;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Use Case: ImpersonateSellerUseCase
 *
 * Permet à un gestionnaire de pharmacie (tenant) d'impersonner un vendeur de son tenant.
 * Nécessite la permission pharmacy.seller.edit.
 * Le vendeur cible doit appartenir au même tenant que l'utilisateur connecté.
 *
 * @package Src\Domains\User\UseCases
 */
class ImpersonateSellerUseCase
{
    /**
     * Impersonner un vendeur du tenant
     *
     * @param int $targetUserId ID du vendeur à impersonner
     * @throws \Exception Si le vendeur n'existe pas, n'est pas du tenant, ou est inactif
     */
    public function execute(int $targetUserId): void
    {
        /** @var UserModel|null $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser) {
            throw new \Exception('User must be authenticated to impersonate');
        }

        if (!$currentUser->isRoot() && !$currentUser->hasPermission('pharmacy.seller.edit')) {
            throw new \Exception('Permission denied: pharmacy.seller.edit required');
        }

        $targetUser = UserModel::findOrFail($targetUserId);

        if ($targetUser->type !== 'SELLER') {
            throw new \Exception('Can only impersonate SELLER users');
        }

        $tenantId = $currentUser->tenant_id;
        if ($tenantId === null || $targetUser->tenant_id !== $tenantId) {
            throw new \Exception('Can only impersonate sellers from your own tenant');
        }

        if ($targetUser->status !== 'active') {
            throw new \Exception('Cannot impersonate inactive user');
        }

        Session::put('impersonate.original_user_id', $currentUser->id);
        Session::put('impersonate.impersonating', true);

        Auth::login($targetUser);

        // Forcer la sauvegarde de la session avant la redirection (évite 403 sur la page cible)
        Session::save();
    }
}
