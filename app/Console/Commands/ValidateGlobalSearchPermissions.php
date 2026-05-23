<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Src\Infrastructure\Search\Repositories\GlobalSearchProvider;

class ValidateGlobalSearchPermissions extends Command
{
    protected $signature = 'search:validate-permissions';

    protected $description = 'Vérifie les permissions référencées dans GlobalSearchProvider';

    public function handle(): int
    {
        $provider = new GlobalSearchProvider();
        $codes = [];
        foreach ($provider->getAllSearchableItems() as $item) {
            $p = $item->getRequiredPermission();
            if ($p !== null && $p !== '*') {
                $codes[$p] = true;
            }
        }

        $existing = Permission::query()
            ->where('is_old', false)
            ->whereIn('code', array_keys($codes))
            ->pluck('code')
            ->all();
        $existingSet = array_flip($existing);

        $missing = [];
        foreach (array_keys($codes) as $code) {
            if (! isset($existingSet[$code])) {
                $missing[] = $code;
            }
        }

        if ($missing === []) {
            $this->info('OK — '.count($codes).' permission(s) référencée(s), toutes présentes en base.');

            return self::SUCCESS;
        }

        sort($missing);
        $this->warn(count($missing).' permission(s) absente(s) de la table permissions :');
        foreach ($missing as $code) {
            $this->line("  - {$code}");
        }

        return self::FAILURE;
    }
}
