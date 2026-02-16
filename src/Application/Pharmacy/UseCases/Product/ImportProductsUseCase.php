<?php

namespace Src\Application\Pharmacy\UseCases\Product;

use Src\Application\Pharmacy\DTO\CreateProductDTO;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Cas d'utilisation : Importation de produits depuis fichier XLSX ou CSV.
 * Valide colonnes, types, doublons. Retourne un résumé (succès/échecs/détails).
 */
class ImportProductsUseCase
{
    private const REQUIRED_COLUMNS = ['nom', 'code', 'categorie_id', 'prix', 'unite'];
    private const OPTIONAL_COLUMNS = ['description', 'prix_revient', 'stock_minimum', 'fabricant', 'type_medicament', 'dosage', 'ordonnance'];
    private const COLUMN_ALIASES = [
        'name' => 'nom', 'product_name' => 'nom', 'produit' => 'nom', 'nom' => 'nom',
        'product_code' => 'code', 'reference' => 'code', 'sku' => 'code', 'code' => 'code',
        'category_id' => 'categorie_id', 'category' => 'categorie_id', 'categorie_id' => 'categorie_id', 'categorie' => 'categorie_id',
        'price' => 'prix', 'prix_vente' => 'prix',
        'unit' => 'unite', 'unity' => 'unite',
        'cost' => 'prix_revient', 'prix_de_revient' => 'prix_revient',
        'minimum_stock' => 'stock_minimum', 'stock_min' => 'stock_minimum',
        'manufacturer' => 'fabricant',
        'medicine_type' => 'type_medicament',
        'description' => 'description',
        'prescription' => 'ordonnance', 'ordonnance_requise' => 'ordonnance',
    ];

    public function __construct(
        private CreateProductUseCase $createProductUseCase,
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * @return array{success: int, failed: int, errors: array<int, string>, total: int}
     */
    public function execute(string $shopId, string $filePath): array
    {
        $rows = $this->parseFile($filePath);
        if (empty($rows)) {
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => [1 => 'Fichier vide ou format invalide'],
                'total' => 0,
            ];
        }

        $header = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $normalizedHeader = $this->normalizeHeader($header);
        $validationError = $this->validateColumns($normalizedHeader);
        if ($validationError) {
            return [
                'success' => 0,
                'failed' => count($rows) - 1,
                'errors' => [1 => $validationError],
                'total' => count($rows) - 1,
            ];
        }

        $success = 0;
        $errors = [];
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $index => $rawRow) {
            $lineNum = $index + 2;
            $row = $this->mapRowToArray($rawRow, $normalizedHeader);
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $dto = $this->buildCreateProductDTO($shopId, $row);
                $product = $this->createProductUseCase->execute($dto);
                $this->persistInfraFields($product->getId(), $row);
                $success++;
            } catch (\Throwable $e) {
                $errors[$lineNum] = $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'failed' => count($errors),
            'errors' => $errors,
            'total' => count($dataRows),
        ];
    }

    private function parseFile(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $reader = new Csv();
            $reader->setDelimiter($this->detectCsvDelimiter($path));
            $reader->setInputEncoding('UTF-8');
            $spreadsheet = $reader->load($path);
        } else {
            $spreadsheet = IOFactory::load($path);
        }

        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray();
    }

    private function detectCsvDelimiter(string $path): string
    {
        $line = fgets(fopen($path, 'r'));
        return strpos($line, ';') !== false ? ';' : ',';
    }

    private function normalizeHeader(array $header): array
    {
        $result = [];
        foreach ($header as $i => $col) {
            $col = preg_replace('/\s+/', '_', trim($col));
            $col = str_replace(['-', 'é', 'è', 'ê'], ['_', 'e', 'e', 'e'], $col);
            $key = self::COLUMN_ALIASES[$col] ?? $col;
            $result[$i] = $key;
        }
        return $result;
    }

    private function validateColumns(array $normalizedHeader): ?string
    {
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $req) {
            if (!in_array($req, $normalizedHeader, true)) {
                $missing[] = $req;
            }
        }
        if (!empty($missing)) {
            return 'Colonnes obligatoires manquantes : ' . implode(', ', $missing);
        }
        return null;
    }

    private function mapRowToArray(array $rawRow, array $header): array
    {
        $row = [];
        foreach ($header as $i => $colName) {
            $row[$colName] = $rawRow[$i] ?? null;
        }
        return $row;
    }

    private function buildCreateProductDTO(string $shopId, array $row): CreateProductDTO
    {
        $code = $this->toString($row['code'] ?? '');
        $name = $this->toString($row['nom'] ?? '');
        $categoryId = $this->resolveCategoryId($shopId, $this->toString($row['categorie_id'] ?? ''));
        $price = $this->toFloat($row['prix'] ?? 0);
        $unit = $this->toString($row['unite'] ?? 'unité');
        $minimumStock = (int) $this->toFloat($row['stock_minimum'] ?? 0);
        $cost = $this->toFloatOrNull($row['prix_revient'] ?? null);
        $prescription = in_array(strtolower((string) ($row['ordonnance'] ?? '')), ['1', 'oui', 'yes', 'true'], true);

        if (empty($code)) {
            throw new \InvalidArgumentException('Code produit obligatoire');
        }
        if (empty($name)) {
            throw new \InvalidArgumentException('Nom produit obligatoire');
        }
        if (empty($categoryId)) {
            throw new \InvalidArgumentException('Catégorie obligatoire');
        }
        if ($price < 0) {
            throw new \InvalidArgumentException('Prix invalide');
        }

        return new CreateProductDTO(
            $shopId,
            $code,
            $name,
            $this->toString($row['description'] ?? null),
            $categoryId,
            $price,
            'USD',
            $cost,
            max(0, $minimumStock),
            $unit,
            $this->toStringOrNull($row['type_medicament'] ?? null),
            $this->toStringOrNull($row['dosage'] ?? null),
            $prescription,
            $this->toStringOrNull($row['fabricant'] ?? null),
            null
        );
    }

    private function persistInfraFields(string $productId, array $row): void
    {
        $data = [];
        if (isset($row['prix_revient']) && $row['prix_revient'] !== '' && $row['prix_revient'] !== null) {
            $data['cost_amount'] = $this->toFloat($row['prix_revient']);
        }
        if (isset($row['fabricant']) && $row['fabricant'] !== '' && $row['fabricant'] !== null) {
            $data['manufacturer'] = $this->toString($row['fabricant']);
        }
        if (!empty($data)) {
            \Src\Infrastructure\Pharmacy\Models\ProductModel::where('id', $productId)->update($data);
        }
    }

    private function resolveCategoryId(string $shopId, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Catégorie obligatoire');
        }
        $category = $this->categoryRepository->findById($value);
        if ($category && $category->getShopId() === $shopId) {
            return $category->getId();
        }
        $category = $this->categoryRepository->findByName($value, $shopId);
        if ($category) {
            return $category->getId();
        }
        throw new \InvalidArgumentException("Catégorie introuvable : {$value}");
    }

    private function toString($val): string
    {
        return trim((string) $val);
    }

    private function toStringOrNull($val): ?string
    {
        $s = trim((string) $val);
        return $s === '' ? null : $s;
    }

    private function toFloat($val): float
    {
        $val = preg_replace('/[^\d.,\-]/', '', (string) $val);
        $val = str_replace(',', '.', $val);
        return (float) $val;
    }

    private function toFloatOrNull($val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        return $this->toFloat($val);
    }
}
