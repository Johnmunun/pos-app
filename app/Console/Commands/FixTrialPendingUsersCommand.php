<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixTrialPendingUsersCommand extends Command
{
    protected $signature = 'trial:fix-pending-users
                            {--dry-run : Simuler sans écrire en base}
                            {--limit=0 : Nombre max d\'utilisateurs à traiter (0 = tous)}';

    protected $description = 'Active les utilisateurs pending et assigne le rôle module selon le secteur';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = User::query()
            ->where('status', 'pending')
            ->whereNotNull('tenant_id')
            ->with('tenant');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            $this->info('Aucun utilisateur pending à corriger.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d utilisateur(s) pending.',
            $dryRun ? '[DRY RUN] Analyse de' : 'Traitement de',
            $users->count()
        ));

        $activated = 0;
        $rolesAssigned = 0;
        $skippedNoSector = 0;

        foreach ($users as $user) {
            $tenant = $user->tenant;
            $sector = strtolower(trim((string) ($tenant->sector ?? '')));

            if ($sector === '') {
                $skippedNoSector++;
                $this->warn("User #{$user->id} ignoré: secteur tenant introuvable.");
                continue;
            }

            $role = $this->resolveRoleForSector($sector, $dryRun);

            if (!$dryRun) {
                DB::transaction(function () use ($user, $role): void {
                    $user->status = 'active';
                    if (Schema::hasColumn('users', 'is_active')) {
                        $user->is_active = true;
                    }
                    $user->save();

                    if ($role) {
                        $alreadyAssigned = DB::table('user_role')
                            ->where('user_id', (int) $user->id)
                            ->where('role_id', (int) $role->id)
                            ->where('tenant_id', (int) $user->tenant_id)
                            ->exists();

                        if (!$alreadyAssigned) {
                            DB::table('user_role')->insert([
                                'user_id' => (int) $user->id,
                                'role_id' => (int) $role->id,
                                'tenant_id' => (int) $user->tenant_id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
            }

            $activated++;
            if ($role) {
                $rolesAssigned++;
            }

            $roleLabel = $role ? $role->name : 'aucun rôle trouvé';
            $this->line("User #{$user->id} ({$user->email}) => active, rôle: {$roleLabel}");
        }

        $this->newLine();
        $this->info('Résultat:');
        $this->line("- Utilisateurs activés: {$activated}");
        $this->line("- Rôles assignés: {$rolesAssigned}");
        $this->line("- Ignorés (sans secteur): {$skippedNoSector}");

        if ($dryRun) {
            $this->warn('Mode dry-run: aucune écriture en base.');
        }

        return self::SUCCESS;
    }

    private function resolveRoleForSector(string $sector, bool $dryRun): ?Role
    {
        $commerceSectors = ['kiosk', 'supermarket', 'butchery', 'other', 'global_commerce'];

        $roleCandidates = match (true) {
            $sector === 'ecommerce' => ['e-commerce', 'Commerçant E-commerce'],
            in_array($sector, $commerceSectors, true) => ['Global commerce', 'Commerçant Commerce', 'Vendeur Commerce'],
            $sector === 'pharmacy' => ['user_pharmacy', 'Commerçant Pharmacie', 'Vendeur Pharmacie'],
            $sector === 'hardware' => ['user_quin', 'Commerçant Hardware', 'Vendeur Hardware'],
            default => [],
        };

        foreach ($roleCandidates as $roleName) {
            $role = Role::query()->where('name', $roleName)->whereNull('tenant_id')->first();
            if ($role) {
                return $role;
            }
        }

        if ($dryRun) {
            return null;
        }

        $fallback = match (true) {
            $sector === 'ecommerce' => [
                'name' => 'e-commerce',
                'codes' => ['module.ecommerce', 'ecommerce.view', 'ecommerce.create', 'ecommerce.update', 'ecommerce.manage_orders'],
            ],
            in_array($sector, $commerceSectors, true) => [
                'name' => 'Global commerce',
                'codes' => ['module.commerce', 'commerce.product.view', 'commerce.sales.view', 'commerce.sales.manage'],
            ],
            $sector === 'pharmacy' => [
                'name' => 'user_pharmacy',
                'codes' => ['module.pharmacy', 'pharmacy.product.view', 'pharmacy.sales.view', 'pharmacy.sales.manage'],
            ],
            $sector === 'hardware' => [
                'name' => 'user_quin',
                'codes' => ['module.hardware', 'hardware.product.view', 'hardware.sales.view', 'hardware.sales.manage'],
            ],
            default => null,
        };

        if ($fallback === null) {
            return null;
        }

        $role = Role::create([
            'tenant_id' => null,
            'name' => $fallback['name'],
            'description' => 'Rôle créé automatiquement par trial:fix-pending-users.',
            'is_active' => true,
        ]);

        $permissionIds = Permission::query()
            ->where('is_old', false)
            ->whereIn('code', $fallback['codes'])
            ->pluck('id')
            ->toArray();

        if (!empty($permissionIds)) {
            $role->permissions()->sync($permissionIds);
        }

        return $role;
    }
}
