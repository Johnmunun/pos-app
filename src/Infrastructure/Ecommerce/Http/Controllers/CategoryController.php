<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Application\GlobalCommerce\Inventory\DTO\CreateCategoryDTO;
use Src\Application\GlobalCommerce\Inventory\DTO\UpdateCategoryDTO;
use Src\Application\GlobalCommerce\Inventory\UseCases\CreateCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\UpdateCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\DeleteCategoryUseCase;

class CategoryController
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CreateCategoryUseCase $createCategoryUseCase,
        private readonly UpdateCategoryUseCase $updateCategoryUseCase,
        private readonly DeleteCategoryUseCase $deleteCategoryUseCase
    ) {}

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) abort(403, 'Shop ID not found.');
        if ($isRoot && !$shopId) abort(403, 'Please select a shop first.');
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les catégories.');
        }

        $shopId = $this->getShopId($request);

        // Récupérer les catégories à partir du modèle Eloquent (liste plate)
        $categoryModels = CategoryModel::where('shop_id', $shopId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categoriesData = [];
        foreach ($categoryModels as $model) {
            $products = $this->productRepository->search($shopId, '', ['category_id' => (string) $model->id]);
            $categoriesData[] = [
                'id' => (string) $model->id,
                'name' => $model->name,
                'product_count' => count($products),
            ];
        }

        return Inertia::render('Ecommerce/Categories/Index', [
            'categories' => $categoriesData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission('ecommerce.category.create') && !$user->hasPermission('ecommerce.category.manage') && !$user->hasPermission('module.ecommerce')) {
            abort(403, 'Vous n\'avez pas la permission de créer des catégories.');
        }

        $shopId = $this->getShopId($request);

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
            (int) ($validated['sort_order'] ?? 0)
        );

        $this->createCategoryUseCase->execute($dto);

        return redirect()->route('ecommerce.categories.index')
            ->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission('ecommerce.category.update') && !$user->hasPermission('ecommerce.category.manage') && !$user->hasPermission('module.ecommerce')) {
            abort(403, 'Vous n\'avez pas la permission de modifier des catégories.');
        }

        $shopId = $this->getShopId($request);

        // Vérifier que la catégorie appartient bien à ce shop
        $categoryModel = CategoryModel::find($id);
        if (!$categoryModel || (string) $categoryModel->shop_id !== $shopId) {
            return redirect()->route('ecommerce.categories.index')
                ->with('error', 'Catégorie introuvable.');
        }

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
            (bool) ($validated['is_active'] ?? true)
        );

        $this->updateCategoryUseCase->execute($dto);

        return redirect()->route('ecommerce.categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }
}
