<?php

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
        private DeleteCategoryUseCase $deleteCategoryUseCase
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
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $categoriesPaginated = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage)->withQueryString();

        $categories = $categoriesPaginated->map(function ($model) {
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

            $this->createCategoryUseCase->execute($dto);

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
            $this->deleteCategoryUseCase->execute($id);
            return redirect()->route('hardware.categories.index')->with('success', 'Catégorie supprimée avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Quincaillerie Category delete error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['message' => 'Erreur lors de la suppression.']);
        }
    }
}
