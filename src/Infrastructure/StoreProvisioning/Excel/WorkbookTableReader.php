<?php

namespace Src\Infrastructure\StoreProvisioning\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lit un classeur Excel (.xlsx) : première ligne = en-têtes, lignes suivantes = données.
 * Les clés sont normalisées en snake_case minuscule.
 */
final class WorkbookTableReader
{
    /**
     * @return list<array<string, string|null>>
     */
    public function readSheet(string $path, ?string $sheetName = null): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $sheetName !== null
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getActiveSheet();
        if ($sheet === null) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $raw = $sheet->toArray();
        if ($raw === [] || $raw[0] === []) {
            return [];
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $raw[0]);
        $out = [];
        for ($i = 1; $i < count($raw); $i++) {
            $row = $raw[$i];
            $assoc = [];
            $empty = true;
            foreach ($headers as $colIdx => $key) {
                if ($key === '') {
                    continue;
                }
                $val = $row[$colIdx] ?? null;
                if ($val !== null && $val !== '') {
                    $empty = false;
                }
                $assoc[$key] = $val === null ? null : (is_scalar($val) ? (string) $val : null);
            }
            if (!$empty) {
                $out[] = $assoc;
            }
        }

        return $out;
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = str_replace([' ', '-'], '_', $h);

        return $h;
    }
}
