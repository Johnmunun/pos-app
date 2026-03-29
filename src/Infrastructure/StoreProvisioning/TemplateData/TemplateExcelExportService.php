<?php

namespace Src\Infrastructure\StoreProvisioning\TemplateData;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class TemplateExcelExportService
{
    /**
     * @param  list<int|string|null>  $headerRow
     * @param  list<list<int|string|null>>  $dataRows
     */
    public function writeSheet(string $path, array $headerRow, array $dataRows): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headerRow], null, 'A1');
        if ($dataRows !== []) {
            $sheet->fromArray($dataRows, null, 'A2');
        }
        (new Xlsx($spreadsheet))->save($path);
    }
}
