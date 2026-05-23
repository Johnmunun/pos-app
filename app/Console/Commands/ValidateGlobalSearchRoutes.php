<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Search\Repositories\GlobalSearchProvider;

class ValidateGlobalSearchRoutes extends Command
{
    protected $signature = 'search:validate-routes {--fix-hints : Afficher les routes proches}';

    protected $description = 'Vérifie que toutes les routes du GlobalSearchProvider existent';

    public function handle(): int
    {
        $provider = new GlobalSearchProvider();
        $items = $provider->getAllSearchableItems();
        $registered = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->unique()->values()->all();
        $registeredSet = array_flip($registered);

        $missing = [];
        $seen = [];

        foreach ($items as $item) {
            $name = $item->getRouteName();
            $key = $name.'|'.$item->getLabel();
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            if (! isset($registeredSet[$name])) {
                $missing[] = [
                    'route' => $name,
                    'label' => $item->getLabel(),
                    'module' => $item->getModule(),
                ];
            }
        }

        if ($missing === []) {
            $this->info('OK — '.count($seen).' route(s) unique(s), toutes enregistrées.');

            return self::SUCCESS;
        }

        $this->error(count($missing).' route(s) introuvable(s) :');
        $this->table(['Route', 'Label', 'Module'], array_map(
            fn ($m) => [$m['route'], $m['label'], $m['module']],
            $missing
        ));

        if ($this->option('fix-hints')) {
            foreach ($missing as $m) {
                $hints = $this->suggestRoutes($m['route'], $registered);
                if ($hints !== []) {
                    $this->line("  {$m['route']} → ".implode(', ', array_slice($hints, 0, 3)));
                }
            }
        }

        return self::FAILURE;
    }

    /**
     * @param list<string> $registered
     * @return list<string>
     */
    private function suggestRoutes(string $missing, array $registered): array
    {
        $prefix = explode('.', $missing)[0] ?? '';
        $hints = [];
        foreach ($registered as $name) {
            if (str_starts_with($name, $prefix.'.') && levenshtein($name, $missing) <= 4) {
                $hints[] = $name;
            }
        }

        return $hints;
    }
}
