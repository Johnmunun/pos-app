<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Src\Domain\Quincaillerie\ValueObjects\TypeUnite;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;

class NormalizeHardwareTypeUniteCommand extends Command
{
    protected $signature = 'hardware:normalize-type-unite
                            {--dry-run : Afficher les changements sans écrire en base}
                            {--shop= : Limiter à un shop_id (UUID)}';

    protected $description = 'Corrige les type_unite invalides (POT, TUBE, etc.) sur les produits quincaillerie existants';

    public function handle(): int
    {
        if (! Schema::hasTable('quincaillerie_products')) {
            $this->warn('Table quincaillerie_products introuvable.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $shopId = $this->option('shop');

        $valid = TypeUnite::getAllTypes();
        $query = ProductModel::query();
        if ($shopId !== null && $shopId !== '') {
            $query->where('shop_id', $shopId);
        }

        $products = $query->get(['id', 'shop_id', 'code', 'name', 'type_unite']);
        if ($products->isEmpty()) {
            $this->info('Aucun produit quincaillerie trouvé.');

            return self::SUCCESS;
        }

        $updated = 0;
        $alreadyOk = 0;
        $changes = [];

        foreach ($products as $product) {
            $current = strtoupper(trim((string) ($product->type_unite ?? '')));
            $normalized = TypeUnite::normalizeValue($product->type_unite);

            if ($current === $normalized && in_array($current, $valid, true)) {
                $alreadyOk++;

                continue;
            }

            $changes[] = [
                'id' => (string) $product->id,
                'code' => (string) ($product->code ?? ''),
                'name' => (string) ($product->name ?? ''),
                'from' => $current !== '' ? $current : '(vide)',
                'to' => $normalized,
            ];

            if (! $dryRun) {
                $product->type_unite = $normalized;
                $product->save();
            }
            $updated++;
        }

        if ($changes !== []) {
            $this->table(['ID', 'Code', 'Nom', 'Avant', 'Après'], array_map(
                fn (array $r) => [$r['id'], $r['code'], $r['name'], $r['from'], $r['to']],
                array_slice($changes, 0, 50)
            ));
            if (count($changes) > 50) {
                $this->line('… et '.(count($changes) - 50).' autre(s) produit(s).');
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info($prefix.sprintf(
            '%d produit(s) corrigé(s), %d déjà valide(s), %d analysé(s).',
            $updated,
            $alreadyOk,
            $products->count()
        ));

        if ($dryRun && $updated > 0) {
            $this->comment('Relancez sans --dry-run pour appliquer les corrections.');
        }

        return self::SUCCESS;
    }
}
