<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Src\Application\Pharmacy\UseCases\Category\CreateCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Category\UpdateCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Category\DeleteCategoryUseCase;
use Src\Application\Pharmacy\DTO\CreateCategoryDTO;
use Src\Application\Pharmacy\DTO\UpdateCategoryDTO;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Infrastructure\Pharmacy\Services\CategoryPdfService;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\Billing\Services\FeatureLimitService;

class CategoryController
{
    private function getModule(): string
    {
        $prefix = request()->route()?->getPrefix();
        return $prefix === 'hardware' ? 'Hardware' : 'Pharmacy';
    }

    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CreateCategoryUseCase $createCategoryUseCase,
        private UpdateCategoryUseCase $updateCategoryUseCase,
        private DeleteCategoryUseCase $deleteCategoryUseCase,
        private CategoryPdfService $pdfService,
        private FeatureLimitService $featureLimitService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        // Recherche
        $search = $request->input('search', '');
        
        // Query builder
        $query = \Src\Infrastructure\Pharmacy\Models\CategoryModel::with(['parent', 'children']);
        
        // ROOT peut voir toutes les catégories, autres users uniquement leur shop
        if ($user->isRoot() && !$shopId) {
            // ROOT voit tout
        } else {
            if (!$shopId) {
                abort(403, 'Shop ID not found. Please contact administrator.');
            }
            $query->where('shop_id', $shopId);
        }
        
        // Appliquer la recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $categoriesPaginated = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
        
        // Convertir en array pour Inertia
        $categories = $categoriesPaginated->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'description' => $model->description ?? '',
                'parent_id' => $model->parent_id,
                'sort_order' => $model->sort_order ?? 0,
                'is_active' => (bool) ($model->is_active ?? true),
                'parent' => $model->parent ? [
                    'id' => $model->parent->id,
                    'name' => $model->parent->name,
                ] : null,
                'products_count' => $model->products()->count(),
            ];
        });
        
        $permPrefix = $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy';
        return Inertia::render($this->getModule() . '/Categories/Index', [
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
            'routePrefix' => $permPrefix,
            'permissions' => [
                'view' => $user->hasPermission($permPrefix . '.category.view') || $user->isRoot(),
                'create' => $user->hasPermission($permPrefix . '.category.create') || $user->isRoot(),
                'update' => $user->hasPermission($permPrefix . '.category.update') || $user->isRoot(),
                'delete' => $user->hasPermission($permPrefix . '.category.delete') || $user->isRoot(),
                // Import disponible seulement si une permission dédiée existe
                'import' => $user->hasPermission($permPrefix . '.category.import') || $user->isRoot(),
            ]
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            
            if (!$shopId && !$user->isRoot()) {
                return redirect()->back()
                    ->withErrors(['message' => 'Shop ID not found. Please contact administrator.']);
            }
            $this->featureLimitService->assertCanCreateCategory((string) ($user->tenant_id ?? ''));
            
            // Validation
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:pharmacy_categories,id',
                'sort_order' => 'nullable|integer|min:0'
            ]);

            // Créer le DTO
            $dto = new CreateCategoryDTO(
                $shopId,
                $request->input('name'),
                $request->input('description') ?: null,
                $request->input('parent_id') ?: null,
                (int) ($request->input('sort_order', 0))
            );

            // Exécuter le Use Case
            $category = $this->createCategoryUseCase->execute($dto);

            return redirect()->route('pharmacy.categories.index')
                ->with('success', 'Catégorie créée avec succès');

        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['message' => 'Erreur lors de la création de la catégorie'])
                ->withInput();
        }
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            $isRoot = $user->isRoot();
            
            // Vérifier que la catégorie appartient à cette pharmacie
            /** @var \Src\Infrastructure\Pharmacy\Models\CategoryModel|null $categoryModel */
            $categoryModel = \Src\Infrastructure\Pharmacy\Models\CategoryModel::query()->find($id);
            if (!$categoryModel) {
                return redirect()->back()
                    ->withErrors(['message' => 'Catégorie non trouvée']);
            }
            
            // Vérification d'isolation par pharmacie
            if (!$isRoot && $categoryModel->shop_id !== $shopId) {
                return redirect()->back()
                    ->withErrors(['message' => 'Catégorie non trouvée']);
            }
            
            // Validation
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:pharmacy_categories,id',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean'
            ]);

            // Créer le DTO
            $dto = new UpdateCategoryDTO(
                $id,
                $request->input('name'),
                $request->input('description'),
                $request->input('parent_id'),
                $request->input('sort_order') !== null ? (int) $request->input('sort_order') : null,
                $request->input('is_active') !== null ? (bool) $request->input('is_active') : null
            );

            // Exécuter le Use Case
            $category = $this->updateCategoryUseCase->execute($dto);

            return redirect()->route('pharmacy.categories.index')
                ->with('success', 'Catégorie mise à jour avec succès');

        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['message' => 'Erreur lors de la mise à jour de la catégorie'])
                ->withInput();
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            $isRoot = $user->isRoot();
            
            // Vérifier que la catégorie appartient à cette pharmacie
            /** @var \Src\Infrastructure\Pharmacy\Models\CategoryModel|null $categoryModel */
            $categoryModel = \Src\Infrastructure\Pharmacy\Models\CategoryModel::query()->find($id);
            if (!$categoryModel) {
                return redirect()->back()
                    ->withErrors(['message' => 'Catégorie non trouvée']);
            }
            
            // Vérification d'isolation par pharmacie
            if (!$isRoot && $categoryModel->shop_id !== $shopId) {
                return redirect()->back()
                    ->withErrors(['message' => 'Catégorie non trouvée']);
            }
            
            // Exécuter le Use Case
            $this->deleteCategoryUseCase->execute($id);

            return redirect()->route('pharmacy.categories.index')
                ->with('success', 'Catégorie supprimée avec succès');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error deleting category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['message' => 'Erreur lors de la suppression de la catégorie']);
        }
    }

    public function getTree(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if ($shopId === null) {
            abort(403, 'Shop ID not found.');
        }
        $tree = $this->categoryRepository->getTree($shopId);
        
        return response()->json([
            'categories' => $tree
        ]);
    }

    /**
     * Export des catégories en PDF
     */
    public function exportPdf(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $search = $request->input('search');
        
        try {
            $pdf = $this->pdfService->generateCategoriesPdf($user, $shopId, $search);
            
            $filename = 'categories_' . now()->format('Y-m-d_His') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Error generating categories PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withErrors(['message' => 'Erreur lors de la génération du PDF']);
        }
    }

    /**
     * Modèle Excel pour l'import de catégories.
     * Colonnes: nom, description, parent_id, ordre, actif.
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
        $sheet->setCellValue('A2', 'Antalgiques');
        $sheet->setCellValue('B2', 'Médicaments contre la douleur et la fièvre');
        $sheet->setCellValue('C2', ''); // parent_id vide = catégorie racine
        $sheet->setCellValue('D2', 1); // ordre
        $sheet->setCellValue('E2', 'oui'); // actif (oui/non)

        $filename = 'modele_import_categories_' . now()->format('Ymd_His') . '.xlsx';

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

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if (!$shopId && !$user->isRoot()) {
            return response()->json(['message' => 'Shop ID introuvable. Veuillez sélectionner un dépôt.'], 403);
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
            Log::error('Category import preview: parse error', ['error' => $e->getMessage()]);
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

        // En-tête obligatoire: nom
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

        // Index rapides pour parent et détection de doublons
        $existingCategoriesQuery = \Src\Infrastructure\Pharmacy\Models\CategoryModel::query();
        if (!$user->isRoot() && $shopId) {
            $existingCategoriesQuery->where('shop_id', $shopId);
        }
        $existingCategories = $existingCategoriesQuery->get(['id', 'name']);
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

            // Ligne vide -> ignorer
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
                // Essayer par ID
                $parent = $existingCategories->firstWhere('id', $parentRaw);
                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    // Essayer par nom
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
     * Import effectif des catégories (insère uniquement les lignes valides).
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
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if (!$shopId && !$user->isRoot()) {
            return response()->json(['message' => 'Shop ID introuvable. Veuillez sélectionner un dépôt.'], 403);
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
            Log::error('Category import: parse error', ['error' => $e->getMessage()]);
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

        $existingCategoriesQuery = \Src\Infrastructure\Pharmacy\Models\CategoryModel::query();
        if (!$user->isRoot() && $shopId) {
            $existingCategoriesQuery->where('shop_id', $shopId);
        }
        $existingCategories = $existingCategoriesQuery->get(['id', 'name']);
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
            if ($actifRaw !== '') {
                $val = mb_strtolower($actifRaw);
                $allowed = ['oui', 'non', 'yes', 'no', '1', '0', 'true', 'false'];
                if (!in_array($val, $allowed, true)) {
                    $lineErrors[] = "Valeur 'actif' invalide (utiliser oui/non) : {$actifRaw}.";
                }
            }

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $dto = new CreateCategoryDTO(
                    $shopId,
                    $name,
                    $rowAssoc['description'] !== '' ? $rowAssoc['description'] : null,
                    $parentId,
                    $sortOrder
                );
                $category = $this->createCategoryUseCase->execute($dto);

                // Mettre à jour les caches locaux pour éviter doublons ultérieurs
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
            'message' => 'Import catégories terminé.',
            'success' => $success,
            'failed' => $failed,
            'total' => $total,
            'errors' => $errors,
        ]);
    }
}