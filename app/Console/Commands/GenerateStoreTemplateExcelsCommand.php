<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Infrastructure\StoreProvisioning\TemplateData\SectorTemplateSheetProvider;
use Src\Infrastructure\StoreProvisioning\TemplateData\TemplateExcelExportService;

/**
 * Génère les fichiers .xlsx sous database/data-templates/{secteur}/.
 * À lancer après modification des définitions ou pour initialiser le dépôt.
 */
class GenerateStoreTemplateExcelsCommand extends Command
{
    protected $signature = 'store:generate-template-excels';

    protected $description = 'Génère les classeurs Excel des packs métier (pharmacy, hardware, global-commerce, ecommerce)';

    public function handle(TemplateExcelExportService $export): int
    {
        $base = database_path('data-templates');
        $today = now()->toDateString();

        foreach (SectorTemplateSheetProvider::sectors() as $folder => $method) {
            $dir = $base.'/'.$folder;
            $data = \call_user_func([SectorTemplateSheetProvider::class, $method], $today);

            foreach (['currencies', 'exchange_rates', 'categories', 'products'] as $key) {
                $sheet = $data[$key];
                $path = $dir.'/'.$key.'.xlsx';
                $export->writeSheet($path, $sheet['header'], $sheet['rows']);
                $this->line($path);
            }
        }

        $this->info('Fichiers Excel générés.');

        return self::SUCCESS;
    }
}
