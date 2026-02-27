<?php

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Src\Application\Quincaillerie\UseCases\Product\CreateProductUseCase;
use Src\Application\Quincaillerie\UseCases\Product\UpdateProductUseCase;
use Src\Application\Quincaillerie\UseCases\Product\GenerateProductCodeUseCase;
use Src\Application\Quincaillerie\DTO\CreateProductDTO;
use Src\Application\Quincaillerie\DTO\UpdateProductDTO;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel;

/**
 * Contrôleur Produits - Module Quincaillerie.
 * Utilise uniquement le domaine Quincaillerie. Aucune dépendance Pharmacy.
 */
class ProductController
{
    private function getShopId(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = null;
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shopByDepot = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }
        if ($shopId === null) {
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        return $shopId ? (string) $shopId : null;
    }

    public function __construct(
        private CreateProductUseCase $createProductUseCase,
        private UpdateProductUseCase $updateProductUseCase,
        private GenerateProductCodeUseCase $generateProductCodeUseCase,
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function generateCode(Request $request): JsonResponse
    {
        $name = (string) $request->input('name', '');
        $shopId = $this->getShopId($request);
        $productCode = $this->generateProductCodeUseCase->execute($name, $shopId);
        return response()->json(['code' => $productCode->getValue()]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        $query = ProductModel::with('category')->orderBy('name');
        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $productModels = $query->get();
        $products = $productModels->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'product_code' => $model->code ?? '',
                'description' => $model->description ?? '',
                'category_id' => $model->category_id,
                'price_amount' => (float) ($model->price_amount ?? 0),
                'price_currency' => $model->price_currency ?? 'USD',
                'minimum_stock' => (float) ($model->minimum_stock ?? 0),
                'current_stock' => (float) ($model->stock ?? 0),
                'is_active' => (bool) ($model->is_active ?? true),
                'type_unite' => $model->type_unite ?? 'UNITE',
                'quantite_par_unite' => (int) ($model->quantite_par_unite ?? 1),
                'est_divisible' => (bool) ($model->est_divisible ?? true),
                'category' => $model->category ? [
                    'id' => $model->category->id,
                    'name' => $model->category->name,
                ] : null,
            ];
        })->toArray();

        $categoryQuery = CategoryModel::query()->where('is_active', true)->orderBy('name');
        if ($shopId !== null) {
            $categoryQuery->where('shop_id', $shopId);
        }
        $categoryModels = $categoryQuery->get();
        $categories = $categoryModels->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'description' => $m->description,
        ])->toArray();

        return Inertia::render('Hardware/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status']),
            'canImport' => false,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        if (!$shopId) {
            abort(403, 'Veuillez sélectionner un dépôt en haut.');
        }
        $categories = $this->categoryRepository->findByShop($shopId, true);
        return Inertia::render('Hardware/Products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            if (!$shopId) {
                return response()->json(['message' => 'Veuillez sélectionner un dépôt en haut.'], 403);
            }
            $request->validate([
                'name' => 'required|string|max:255',
                'product_code' => 'required|string|max:50',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:quincaillerie_categories,id',
                'price' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'minimum_stock' => 'required|integer|min:0',
                'unit' => 'nullable|string|max:50',
                'type_unite' => 'required|string|in:PIECE,LOT,METRE,KG,LITRE,BOITE,CARTON,UNITE',
                'quantite_par_unite' => 'required|integer|min:1',
                'est_divisible' => 'boolean',
            ]);

            $dto = new CreateProductDTO(
                $shopId,
                $request->input('product_code'),
                $request->input('name'),
                $request->input('category_id'),
                (float) $request->input('price'),
                $request->input('currency'),
                (int) $request->input('minimum_stock'),
                $request->input('unit', 'UNITE'),
                $request->input('description') ?: null,
                null,
                null,
                $request->input('type_unite', 'UNITE'),
                (int) $request->input('quantite_par_unite', 1),
                $request->boolean('est_divisible', true)
            );

            $product = $this->createProductUseCase->execute($dto);

            return response()->json([
                'message' => 'Produit créé avec succès.',
                'product' => ['id' => $product->getId()],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $product = $this->productRepository->findById($id);
        if (!$product) {
            abort(404, 'Produit introuvable.');
        }
        $shopId = $this->getShopId($request);
        if ($shopId && $product->getShopId() !== $shopId) {
            abort(404, 'Produit introuvable.');
        }

        $model = ProductModel::with('category')->find($id);
        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'product_code' => $product->getCode()->getValue(),
            'description' => $product->getDescription(),
            'category_id' => $product->getCategoryId(),
            'price_amount' => $product->getPrice()->getAmount(),
            'price_currency' => $product->getPrice()->getCurrency(),
            'current_stock' => $product->getStock()->getValue(),
            'minimum_stock' => $product->getMinimumStock()->getValue(),
            'type_unite' => $product->getTypeUnite()->getValue(),
            'quantite_par_unite' => $product->getQuantiteParUnite(),
            'est_divisible' => $product->estDivisible(),
            'is_active' => $product->isActive(),
            'category' => $model && $model->category ? ['id' => $model->category->id, 'name' => $model->category->name] : null,
        ];

        return Inertia::render('Hardware/Products/Show', [
            'product' => $productData,
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        if (!$shopId) {
            abort(403, 'Veuillez sélectionner un dépôt en haut.');
        }
        $product = $this->productRepository->findById($id);
        if (!$product || $product->getShopId() !== $shopId) {
            abort(404, 'Produit introuvable.');
        }
        $categories = $this->categoryRepository->findByShop($shopId, true);
        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'product_code' => $product->getCode()->getValue(),
            'description' => $product->getDescription(),
            'category_id' => $product->getCategoryId(),
            'price_amount' => $product->getPrice()->getAmount(),
            'price_currency' => $product->getPrice()->getCurrency(),
            'minimum_stock' => (int) $product->getMinimumStock()->getValue(),
            'type_unite' => $product->getTypeUnite()->getValue(),
            'quantite_par_unite' => $product->getQuantiteParUnite(),
            'est_divisible' => $product->estDivisible(),
        ];
        return Inertia::render('Hardware/Products/Edit', [
            'product' => $productData,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            if (!$shopId) {
                return response()->json(['message' => 'Veuillez sélectionner un dépôt en haut.'], 403);
            }
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:quincaillerie_categories,id',
                'price' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'minimum_stock' => 'nullable|integer|min:0',
                'type_unite' => 'required|string|in:PIECE,LOT,METRE,KG,LITRE,BOITE,CARTON,UNITE',
                'quantite_par_unite' => 'required|integer|min:1',
                'est_divisible' => 'boolean',
            ]);

            $dto = new UpdateProductDTO(
                $id,
                $request->input('name'),
                $request->input('description'),
                (float) $request->input('price'),
                $request->input('currency'),
                $request->input('category_id'),
                $request->input('type_unite', 'UNITE'),
                (int) $request->input('quantite_par_unite', 1),
                $request->boolean('est_divisible', true),
                $request->filled('minimum_stock') ? (int) $request->input('minimum_stock') : null
            );

            $this->updateProductUseCase->execute($dto);

            return response()->json(['message' => 'Produit mis à jour avec succès.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $product = $this->productRepository->findById($id);
            if (!$product) {
                return response()->json(['message' => 'Produit introuvable.'], 404);
            }
            $shopId = $this->getShopId($request);
            if ($shopId && $product->getShopId() !== $shopId) {
                return response()->json(['message' => 'Produit introuvable.'], 404);
            }
            $this->productRepository->delete($id);
            return response()->json(['message' => 'Produit supprimé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
