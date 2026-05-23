<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DefaultSellerRoleResolver;
use Illuminate\Console\Command;
use Src\Domains\User\UseCases\AssignUserRoleUseCase;

/**
 * Assigne le rôle système « Vendeur * » aux comptes SELLER sans rôle (rattrapage prod).
 */
class AssignDefaultRolesToSellers extends Command
{
    protected $signature = 'sellers:assign-missing-roles {--dry-run : Afficher sans modifier}';

    protected $description = 'Assigne le rôle vendeur par défaut aux comptes SELLER sans rôle';

    public function handle(AssignUserRoleUseCase $assignRoleUseCase): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        User::query()
            ->where('type', 'SELLER')
            ->whereNotNull('tenant_id')
            ->withCount(['roles'])
            ->having('roles_count', '=', 0)
            ->chunkById(100, function ($sellers) use ($assignRoleUseCase, $dryRun, &$count) {
                foreach ($sellers as $seller) {
                    $roleIds = DefaultSellerRoleResolver::roleIdsForTenant($seller->tenant_id);
                    if ($roleIds === []) {
                        $this->warn("Aucun rôle par défaut pour le tenant #{$seller->tenant_id} (vendeur #{$seller->id}).");
                        continue;
                    }
                    if ($dryRun) {
                        $this->line("Vendeur #{$seller->id} ({$seller->email}) → rôle(s) ".implode(',', $roleIds));
                    } else {
                        $assignRoleUseCase->assignRolesForTenant(
                            userId: $seller->id,
                            roleIds: $roleIds,
                            tenantId: (int) $seller->tenant_id
                        );
                    }
                    $count++;
                }
            });

        $this->info($dryRun
            ? "{$count} vendeur(s) seraient mis à jour."
            : "{$count} vendeur(s) mis à jour.");

        return self::SUCCESS;
    }
}
