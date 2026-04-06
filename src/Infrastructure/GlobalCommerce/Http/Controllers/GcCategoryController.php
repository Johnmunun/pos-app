<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Application\GlobalCommerce\Inventory\DTO\CreateCategoryDTO;
use Src\Application\GlobalCommerce\Inventory\DTO\UpdateCategoryDTO;
use Src\Application\GlobalCommerce\Inventory\UseCases\CreateCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\DeleteCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\UpdateCategoryUseCase;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Services\TenantBackofficeShopResolver;

class GcCategoryController
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CreateCategoryUseCase $createCategoryUseCase,
        private UpdateCategoryUseCase $updateCategoryUseCase,
        private DeleteCategoryUseCase $deleteCategoryUseCase,
        private TenantBackofficeShopResolver $shopResolver,
    ) {}

    /**
     * @return array{shop: Shop, shopId: string, gcIds: list<string>}
     */
    private function gcScope(Request $request): array
    {
        $shop = $this->shopResolver->resolveShop($request);
        $user = $request->user();
        $tenantId = $user && $user->tenant_id !== null && $user->tenant_id !== '' ? (string) $user->tenant_id : null;

        return [
            'shop' => $shop,
            'shopId' => (string) $shop->id,
            'gcIds' => $this->shopResolver->globalCommerceInventoryShopIds($shop, $tenantId),
        ];
    }

    public function index(Request $request): Response
    {
        $gcIds = $this->gcScope($request)['gcIds'];
        $tree = $this->categoryRepository->findTreeForShopIds($gcIds);
        $flat = CategoryModel::whereIn('shop_id', $gcIds)->orderBy('sort_order')->orderBy('name')->get()->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'description' => $m->description,
            'parent_id' => $m->parent_id,
            'sort_order' => $m->sort_order,
            'is_active' => $m->is_active,
        ]);
        return Inertia::render('Commerce/Categories/Index', [
            'tree' => $tree,
            'categories' => $flat,
        ]);
    }

    public function create(Request $request): Response
    {
        $gcIds = $this->gcScope($request)['gcIds'];
        $categories = CategoryModel::whereIn('shop_id', $gcIds)->orderBy('name')->get(['id', 'name', 'parent_id']);
        return Inertia::render('Commerce/Categories/Create', [
            'parentOptions' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:gc_categories,id',
            'sort_order' => 'integer|min:0',
        ]);
        $dto = new CreateCategoryDTO(
            $shopId,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['parent_id'] ?? null,
            (int) ($validated['sort_order'] ?? 0),
            $gcIds
        );
        $this->createCategoryUseCase->execute($dto);
        return redirect()->route('commerce.categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $category = $this->categoryRepository->findById($id);
        if (!$category || !in_array($category->getShopId(), $gcIds, true)) {
            return redirect()->route('commerce.categories.index')->with('error', 'Catégorie introuvable.');
        }
        $parentOptions = CategoryModel::whereIn('shop_id', $gcIds)->where('id', '!=', $id)->orderBy('name')->get(['id', 'name', 'parent_id']);
        return Inertia::render('Commerce/Categories/Edit', [
            'category' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'parent_id' => $category->getParentId(),
                'sort_order' => $category->getSortOrder(),
                'is_active' => $category->isActive(),
            ],
            'parentOptions' => $parentOptions->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id]),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:gc_categories,id',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);
        $dto = new UpdateCategoryDTO(
            $id,
            $shopId,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['parent_id'] ?? null,
            (int) ($validated['sort_order'] ?? 0),
            (bool) ($validated['is_active'] ?? true),
            $gcIds
        );
        $this->updateCategoryUseCase->execute($dto);
        return redirect()->route('commerce.categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $gcIds = $this->gcScope($request)['gcIds'];
        try {
            $this->deleteCategoryUseCase->execute($gcIds, $id);
            return redirect()->route('commerce.categories.index')->with('success', 'Catégorie supprimée.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('commerce.categories.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Modèle Excel pour l'import de catégories Global Commerce.
     */
    public function importTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Catégories Commerce');

        $headers = [
            'nom',
            'description',
            'parent',
            'ordre',
            'actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Exemple
        $sheet->fromArray(
            [
                'OUTILLAGE',
                'Outils et quincaillerie',
                '',
                1,
                'oui',
            ],
            null,
            'A2'
        );

        $filename = 'modele_import_categories_commerce_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aperçu de l'import de catégories (validation sans insertion).
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format .xlsx ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $gcIds = $this->gcScope($request)['gcIds'];
        $file = $request->file('file');
        $path = $file->getRealPath();

        $sampleHeader = [];
        $sampleRows = [];
        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $handle = fopen($path, 'r');
                $line = $handle ? fgets($handle) : null;
                if ($handle) {
                    fclose($handle);
                }
                $delimiter = $line !== null && strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $allRows = $sheet->toArray();
            $sampleHeader = $allRows[0] ?? [];
            $dataRows = array_slice($allRows, 1);
            $sampleRows = array_slice($dataRows, 0, 20);
        } catch (\Throwable $e) {
            Log::error('GcCategory import preview: parse error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Impossible de lire le fichier : ' . $e->getMessage()], 422);
        }

        $rows = $sheet->toArray();
        if (empty($rows)) {
            return response()->json([
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'errors' => [],
                'sample' => ['header' => [], 'rows' => []],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        if (!in_array('nom', $headerRow, true)) {
            return response()->json([
                'message' => "Colonne obligatoire manquante : 'nom'.",
                'total' => count($dataRows),
                'valid' => 0,
                'invalid' => count($dataRows),
                'errors' => [['line' => 1, 'field' => 'nom', 'message' => "Colonne obligatoire manquante : 'nom'."]],
                'sample' => ['header' => $sampleHeader, 'rows' => $sampleRows],
            ], 422);
        }

        $existing = CategoryModel::whereIn('shop_id', $gcIds)->get(['id', 'name']);
        $existingByName = [];
        foreach ($existing as $cat) {
            $existingByName[mb_strtolower($cat->name)] = $cat->id;
        }

        $seenNames = [];
        $valid = 0;
        $invalid = 0;
        $errorsDetailed = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];
            $name = $rowAssoc['nom'] ?? '';
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            } else {
                $lower = mb_strtolower($name);
                if (isset($existingByName[$lower])) {
                    $lineErrors[] = "Catégorie déjà existante : {$name}.";
                }
                if (isset($seenNames[$lower])) {
                    $lineErrors[] = "Nom en double dans le fichier : {$name}.";
                }
            }

            $parentRaw = $rowAssoc['parent'] ?? '';
            if ($parentRaw !== '') {
                $parentId = $existing->firstWhere('id', $parentRaw)?->id ?? ($existingByName[mb_strtolower($parentRaw)] ?? null);
                if ($parentId === null) {
                    $lineErrors[] = "Catégorie parente introuvable : {$parentRaw}.";
                }
            }

            $orderRaw = $rowAssoc['ordre'] ?? '';
            if ($orderRaw !== '' && !is_numeric($orderRaw)) {
                $lineErrors[] = 'Ordre doit être un nombre.';
            }

            if (!empty($lineErrors)) {
                $invalid++;
                $errorsDetailed[] = ['line' => $lineNum, 'field' => null, 'message' => implode(' | ', $lineErrors)];
            } else {
                $valid++;
                if ($name !== '') {
                    $seenNames[mb_strtolower($name)] = true;
                }
            }
        }

        return response()->json([
            'total' => count($dataRows),
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errorsDetailed,
            'sample' => ['header' => $sampleHeader, 'rows' => $sampleRows],
        ]);
    }

    /**
     * Import simple de catégories Global Commerce.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format .xlsx ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $scope = $this->gcScope($request);
        $shopId = $scope['shopId'];
        $gcIds = $scope['gcIds'];

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $handle = fopen($path, 'r');
                $line = $handle ? fgets($handle) : null;
                if ($handle) {
                    fclose($handle);
                }
                $delimiter = $line !== null && strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('GcCategory import: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'Fichier vide.',
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        if (!in_array('nom', $headerRow, true)) {
            return response()->json([
                'message' => "Colonne obligatoire manquante : 'nom'.",
                'total' => count($dataRows),
                'success' => 0,
                'failed' => count($dataRows),
                'errors' => [],
            ], 422);
        }

        $existing = CategoryModel::whereIn('shop_id', $gcIds)->get(['id', 'name']);
        $existingByName = [];
        foreach ($existing as $cat) {
            $existingByName[mb_strtolower($cat->name)] = $cat->id;
        }

        $seenNames = [];
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];

            $name = $rowAssoc['nom'] ?? '';
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            } else {
                $lower = mb_strtolower($name);
                if (isset($existingByName[$lower])) {
                    $lineErrors[] = "Catégorie déjà existante : {$name}.";
                }
                if (isset($seenNames[$lower])) {
                    $lineErrors[] = "Nom en double dans le fichier : {$name}.";
                }
            }

            $parentRaw = $rowAssoc['parent'] ?? '';
            $parentId = null;
            if ($parentRaw !== '') {
                // Par ID
                $parent = $existing->firstWhere('id', $parentRaw);
                if (!$parent) {
                    // Par nom
                    $parentId = $existingByName[mb_strtolower($parentRaw)] ?? null;
                } else {
                    $parentId = $parent->id;
                }
                if ($parentId === null) {
                    $lineErrors[] = "Catégorie parente introuvable : {$parentRaw}.";
                }
            }

            $orderRaw = $rowAssoc['ordre'] ?? '';
            $sortOrder = 0;
            if ($orderRaw !== '') {
                if (!is_numeric($orderRaw)) {
                    $lineErrors[] = 'Ordre doit être un nombre.';
                } else {
                    $sortOrder = (int) $orderRaw;
                }
            }

            $activeRaw = mb_strtolower($rowAssoc['actif'] ?? '');
            $isActive = !in_array($activeRaw, ['non', 'no', '0', 'false'], true);

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $dto = new CreateCategoryDTO(
                    $shopId,
                    $name,
                    $rowAssoc['description'] ?? null,
                    $parentId,
                    $sortOrder,
                    $gcIds
                );
                $category = $this->createCategoryUseCase->execute($dto);

                /** @var CategoryModel|null $model */
                $model = CategoryModel::find($category->getId());
                if ($model) {
                    $model->update(['is_active' => $isActive]);
                }

                $seenNames[mb_strtolower($name)] = true;
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import catégories terminé.',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }
}
