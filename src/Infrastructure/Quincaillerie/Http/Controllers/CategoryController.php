<?php

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Src\Application\Quincaillerie\UseCases\Category\CreateCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Category\UpdateCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Category\DeleteCategoryUseCase;
use Src\Application\Quincaillerie\DTO\CreateCategoryDTO;
use Src\Application\Quincaillerie\DTO\UpdateCategoryDTO;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel;
use Src\Application\Quincaillerie\Services\DepotFilterService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Contrôleur Catégories - Module Quincaillerie.
 * Aucune dépendance Pharmacy.
 */
class CategoryController
{
    private function getShopId(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        return $shopId ? (string) $shopId : null;
    }

    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CreateCategoryUseCase $createCategoryUseCase,
        private UpdateCategoryUseCase $updateCategoryUseCase,
        private DeleteCategoryUseCase $deleteCategoryUseCase,
        private DepotFilterService $depotFilterService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $search = $request->input('search', '');

        $query = CategoryModel::with(['parent', 'children']);
        if ($shopId) {
            $query->where('shop_id', $shopId);
        } elseif (!$user->isRoot()) {
            abort(403, 'Shop ID not found.');
        }
        
        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        /** @var \Illuminate\Pagination\LengthAwarePaginator<\Src\Infrastructure\Quincaillerie\Models\CategoryModel> $categoriesPaginated */
        $categoriesPaginated = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage)->withQueryString();

        $categories = $categoriesPaginated->map(function ($model) {
            /** @var \Src\Infrastructure\Quincaillerie\Models\CategoryModel $model */
            return [
                'id' => $model->id,
                'name' => $model->name,
                'description' => $model->description ?? '',
                'parent_id' => $model->parent_id,
                'sort_order' => $model->sort_order ?? 0,
                'is_active' => (bool) ($model->is_active ?? true),
                'parent' => $model->parent ? ['id' => $model->parent->id, 'name' => $model->parent->name] : null,
                'products_count' => $model->products()->count(),
            ];
        });

        return Inertia::render('Hardware/Categories/Index', [
            'categories' => $categories,
            'pagination' => [
                'current_page' => $categoriesPaginated->currentPage(),
                'last_page' => $categoriesPaginated->lastPage(),
                'per_page' => $categoriesPaginated->perPage(),
                'total' => $categoriesPaginated->total(),
                'from' => $categoriesPaginated->firstItem(),
                'to' => $categoriesPaginated->lastItem(),
            ],
            'filters' => $request->only(['search', 'per_page']),
            'routePrefix' => 'hardware',
            'permissions' => [
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            if (!$shopId && !$user->isRoot()) {
                return redirect()->back()->withErrors(['message' => 'Shop ID not found.'])->withInput();
            }
            if (!$shopId) {
                $shopId = ''; // ROOT sans shop : on pourrait refuser ou prendre le premier tenant
                return redirect()->back()->withErrors(['message' => 'Veuillez sélectionner un magasin.'])->withInput();
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:quincaillerie_categories,id',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $dto = new CreateCategoryDTO(
                $shopId,
                $request->input('name'),
                $request->input('description') ?: null,
                $request->input('parent_id') ?: null,
                (int) ($request->input('sort_order', 0))
            );

            $category = $this->createCategoryUseCase->execute($dto);
            
            // Assigner le dépôt selon les permissions
            $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
            if ($effectiveDepotId !== null) {
                $categoryModel = CategoryModel::find($category->getId());
                if ($categoryModel) {
                    $categoryModel->update(['depot_id' => $effectiveDepotId]);
                }
            }

            return redirect()->route('hardware.categories.index')->with('success', 'Catégorie créée avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            Log::error('Quincaillerie Category create error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['message' => 'Erreur lors de la création.'])->withInput();
        }
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            $categoryModel = CategoryModel::query()->find($id);
            if (!$categoryModel) {
                return redirect()->back()->withErrors(['message' => 'Catégorie non trouvée.']);
            }
            if ($shopId && $categoryModel->shop_id != $shopId) {
                return redirect()->back()->withErrors(['message' => 'Catégorie non trouvée.']);
            }

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:quincaillerie_categories,id',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $dto = new UpdateCategoryDTO(
                $id,
                $request->input('name'),
                $request->input('description'),
                $request->input('parent_id'),
                (int) ($request->input('sort_order', 0))
            );

            $this->updateCategoryUseCase->execute($dto);

            return redirect()->route('hardware.categories.index')->with('success', 'Catégorie mise à jour avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            Log::error('Quincaillerie Category update error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['message' => 'Erreur lors de la mise à jour.'])->withInput();
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            $categoryModel = CategoryModel::query()->find($id);
            if (!$categoryModel) {
                return redirect()->back()->withErrors(['message' => 'Catégorie non trouvée.']);
            }
            if ($shopId && $categoryModel->shop_id != $shopId) {
                return redirect()->back()->withErrors(['message' => 'Catégorie non trouvée.']);
            }
            
            // Vérifier l'accès au dépôt de la catégorie
            $query = CategoryModel::where('id', $id);
            $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');
            if (!$query->exists()) {
                return redirect()->back()->withErrors(['message' => 'Catégorie non trouvée ou accès non autorisé à ce dépôt.']);
            }
            
            $this->deleteCategoryUseCase->execute($id);
            return redirect()->route('hardware.categories.index')->with('success', 'Catégorie supprimée avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Quincaillerie Category delete error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['message' => 'Erreur lors de la suppression.']);
        }
    }

    /**
     * Modèle Excel pour l'import de catégories (Hardware).
     */
    public function importTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Catégories');

        $headers = [
            'nom',
            'description',
            'parent_id',
            'ordre',
            'actif',
        ];

        $colIndex = 1;
        foreach ($headers as $header) {
            $columnLetter = chr(ord('A') + $colIndex - 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            $colIndex++;
        }

        // Exemple de ligne
        $sheet->setCellValue('A2', 'Outils');
        $sheet->setCellValue('B2', 'Outils manuels et électriques');
        $sheet->setCellValue('C2', '');
        $sheet->setCellValue('D2', 1);
        $sheet->setCellValue('E2', 'oui');

        $filename = 'modele_import_categories_hardware_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aperçu de l'import de catégories Hardware (validation sans insertion).
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

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        if (!$shopId && !$user->isRoot()) {
            return response()->json(['message' => 'Shop ID not found.'], 403);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $line = fgets(fopen($path, 'r'));
                $delimiter = strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('Hardware Category import preview: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'errors' => [
                    ['line' => 1, 'field' => null, 'message' => 'Fichier vide.'],
                ],
                'sample' => [
                    'header' => [],
                    'rows' => [],
                ],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        if (!in_array('nom', $headerRow, true)) {
            return response()->json([
                'total' => count($dataRows),
                'valid' => 0,
                'invalid' => count($dataRows),
                'errors' => [
                    [
                        'line' => 1,
                        'field' => 'nom',
                        'message' => "Colonne obligatoire manquante : 'nom'.",
                    ],
                ],
                'sample' => [
                    'header' => $rows[0] ?? [],
                    'rows' => array_slice($dataRows, 0, 20),
                ],
            ]);
        }

        $existingQuery = CategoryModel::query();
        if ($shopId) {
            $existingQuery->where('shop_id', $shopId);
        }
        $existingCategories = $existingQuery->get(['id', 'name']);
        $existingByName = [];
        foreach ($existingCategories as $cat) {
            $existingByName[mb_strtolower($cat->name)] = $cat->id;
        }

        $seenNames = [];
        $valid = 0;
        $invalid = 0;
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
                $lowerName = mb_strtolower($name);
                if (isset($existingByName[$lowerName])) {
                    $lineErrors[] = "Catégorie déjà existante : {$name}.";
                }
                if (isset($seenNames[$lowerName])) {
                    $lineErrors[] = "Nom en double dans le fichier : {$name}.";
                }
            }

            $parentRaw = $rowAssoc['parent_id'] ?? '';
            if ($parentRaw !== '') {
                $parentId = null;
                $parent = $existingCategories->firstWhere('id', $parentRaw);
                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    $parent = $existingCategories->firstWhere('name', $parentRaw);
                    if ($parent) {
                        $parentId = $parent->id;
                    }
                }
                if ($parentId === null) {
                    $lineErrors[] = "Catégorie parente introuvable : {$parentRaw}.";
                }
            }

            $ordreRaw = $rowAssoc['ordre'] ?? '';
            if ($ordreRaw !== '' && !is_numeric($ordreRaw)) {
                $lineErrors[] = 'Ordre doit être un nombre.';
            }

            $actifRaw = $rowAssoc['actif'] ?? '';
            if ($actifRaw !== '') {
                $val = mb_strtolower($actifRaw);
                $allowed = ['oui', 'non', 'yes', 'no', '1', '0', 'true', 'false'];
                if (!in_array($val, $allowed, true)) {
                    $lineErrors[] = "Valeur 'actif' invalide (utiliser oui/non) : {$actifRaw}.";
                }
            }

            if (!empty($lineErrors)) {
                $invalid++;
                $errors[] = [
                    'line' => $lineNum,
                    'field' => null,
                    'message' => implode(' | ', $lineErrors),
                ];
            } else {
                $valid++;
                $seenNames[mb_strtolower($name)] = true;
            }
        }

        $total = $valid + $invalid;

        return response()->json([
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
            'sample' => [
                'header' => $rows[0] ?? [],
                'rows' => array_slice($dataRows, 0, 20),
            ],
        ]);
    }

    /**
     * Import effectif des catégories Hardware.
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

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        if (!$shopId && !$user->isRoot()) {
            return response()->json(['message' => 'Shop ID not found.'], 403);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $line = fgets(fopen($path, 'r'));
                $delimiter = strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('Hardware Category import: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'Fichier vide.',
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'errors' => [],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        if (!in_array('nom', $headerRow, true)) {
            $failed = count($dataRows);
            return response()->json([
                'message' => "Colonne obligatoire manquante : 'nom'.",
                'success' => 0,
                'failed' => $failed,
                'total' => $failed,
                'errors' => ["Ligne 1: Colonne obligatoire manquante : 'nom'."],
            ], 422);
        }

        $existingQuery = CategoryModel::query();
        if ($shopId) {
            $existingQuery->where('shop_id', $shopId);
        }
        $existingCategories = $existingQuery->get(['id', 'name']);
        $existingByName = [];
        foreach ($existingCategories as $cat) {
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
            }

            $parentRaw = $rowAssoc['parent_id'] ?? '';
            $parentId = null;
            if ($parentRaw !== '') {
                $parent = $existingCategories->firstWhere('id', $parentRaw);
                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    $parent = $existingCategories->firstWhere('name', $parentRaw);
                    if ($parent) {
                        $parentId = $parent->id;
                    }
                }
                if ($parentId === null) {
                    $lineErrors[] = "Catégorie parente introuvable : {$parentRaw}.";
                }
            }

            $ordreRaw = $rowAssoc['ordre'] ?? '';
            $sortOrder = 0;
            if ($ordreRaw !== '') {
                if (!is_numeric($ordreRaw)) {
                    $lineErrors[] = 'Ordre doit être un nombre.';
                } else {
                    $sortOrder = (int) $ordreRaw;
                }
            }

            $actifRaw = $rowAssoc['actif'] ?? '';
            $isActive = true;
            if ($actifRaw !== '') {
                $val = mb_strtolower($actifRaw);
                $allowed = ['oui', 'non', 'yes', 'no', '1', '0', 'true', 'false'];
                if (!in_array($val, $allowed, true)) {
                    $lineErrors[] = "Valeur 'actif' invalide (utiliser oui/non) : {$actifRaw}.";
                } else {
                    $isActive = in_array($val, ['oui', 'yes', '1', 'true'], true);
                }
            }

            if ($name !== '') {
                $lowerName = mb_strtolower($name);
                if (isset($existingByName[$lowerName])) {
                    $lineErrors[] = "Catégorie déjà existante : {$name}.";
                }
                if (isset($seenNames[$lowerName])) {
                    $lineErrors[] = "Nom en double dans le fichier : {$name}.";
                }
            }

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $dto = new CreateCategoryDTO(
                    $shopId ?? '',
                    $name,
                    $rowAssoc['description'] !== '' ? $rowAssoc['description'] : null,
                    $parentId,
                    $sortOrder
                );

                $category = $this->createCategoryUseCase->execute($dto);

                // Marquer active/inactive si besoin
                $model = CategoryModel::find($category->getId());
                if ($model) {
                    $update = ['is_active' => $isActive];
                    // Assigner le dépôt si possible
                    $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
                    if ($effectiveDepotId !== null) {
                        $update['depot_id'] = $effectiveDepotId;
                    }
                    $model->update($update);
                }

                $lowerName = mb_strtolower($name);
                $seenNames[$lowerName] = true;
                $existingByName[$lowerName] = $category->getId();

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import catégories Hardware terminé.',
            'success' => $success,
            'failed' => $failed,
            'total' => $total,
            'errors' => $errors,
        ]);
    }
}
