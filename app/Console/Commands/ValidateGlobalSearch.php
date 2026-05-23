<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateGlobalSearch extends Command
{
    protected $signature = 'search:validate {--fix-hints : Suggestions pour routes manquantes}';

    protected $description = 'Valide routes et permissions de la recherche globale (Ctrl+K)';

    public function handle(): int
    {
        $routes = $this->call('search:validate-routes', [
            '--fix-hints' => $this->option('fix-hints'),
        ]);
        $perms = $this->call('search:validate-permissions');

        return ($routes === self::SUCCESS && $perms === self::SUCCESS)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
