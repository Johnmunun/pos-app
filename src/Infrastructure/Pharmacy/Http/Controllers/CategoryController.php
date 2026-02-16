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
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;

class CategoryController
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CreateCategoryUseCase $createCategoryUseCase,
        private UpdateCategoryUseCase $updateCategoryUseCase,
        private DeleteCategoryUseCase $deleteCategoryUseCase,
        private CategoryPdfService $pdfService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
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
        
        return Inertia::render('Pharmacy/Categories/Index', [
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
            'permissions' => [
                'view' => $user->hasPermission('pharmacy.category.view') || $user->isRoot(),
                'create' => $user->hasPermission('pharmacy.category.create') || $user->isRoot(),
                'update' => $user->hasPermission('pharmacy.category.update') || $user->isRoot(),
                'delete' => $user->hasPermission('pharmacy.category.delete') || $user->isRoot(),
            ]
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            
            if (!$shopId && !$user->isRoot()) {
                return redirect()->back()
                    ->withErrors(['message' => 'Shop ID not found. Please contact administrator.']);
            }
            
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
        $tree = $this->categoryRepository->getTree($request->user()->shop_id);
        
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
}