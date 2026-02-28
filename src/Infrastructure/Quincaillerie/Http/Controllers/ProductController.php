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
use Src\Infrastructure\Quincaillerie\Services\ProductImageService;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentProductRepository;
use Src\Application\Quincaillerie\Services\DepotFilterService;
use Illuminate\Support\Facades\Log;
use App\Models\Depot;
use Illuminate\Support\Str;

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
        private CategoryRepositoryInterface $categoryRepository,
        private ProductImageService $imageService,
        private DepotFilterService $depotFilterService
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
        
        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');
        
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
        /** @var \Illuminate\Database\Eloquent\Collection<int, ProductModel> $productModels */
        $products = $productModels->map(function (ProductModel $model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'product_code' => $model->code ?? '',
                'description' => $model->description ?? '',
                'category_id' => $model->category_id,
                'price_amount' => (float) ($model->price_amount ?? 0),
                'price_currency' => $model->price_currency ?? 'USD',
                'image_path' => $model->image_path,
                'image_url' => $model->image_path ? $this->imageService->getUrl($model->image_path, $model->image_type ?: 'upload') : null,
                'price_normal' => $model->price_normal ? (float) $model->price_normal : null,
                'price_reduced' => $model->price_reduced ? (float) $model->price_reduced : null,
                'price_reduction_percent' => $model->price_reduction_percent ? (float) $model->price_reduction_percent : null,
                'price_non_negotiable' => $model->price_non_negotiable ? (float) $model->price_non_negotiable : null,
                'price_wholesale_normal' => $model->price_wholesale_normal ? (float) $model->price_wholesale_normal : null,
                'price_wholesale_reduced' => $model->price_wholesale_reduced ? (float) $model->price_wholesale_reduced : null,
                'price_non_negotiable_wholesale' => $model->price_non_negotiable_wholesale ? (float) $model->price_non_negotiable_wholesale : null,
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

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            if (!$shopId) {
                return redirect()->back()->withErrors(['message' => 'Veuillez sélectionner un dépôt en haut.']);
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
                'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
                'price_normal' => 'nullable|numeric|min:0',
                'price_reduced' => 'nullable|numeric|min:0',
                'price_reduction_percent' => 'nullable|numeric|min:0|max:100',
                'price_non_negotiable' => 'nullable|numeric|min:0',
                'price_wholesale_normal' => 'nullable|numeric|min:0',
                'price_wholesale_reduced' => 'nullable|numeric|min:0',
                'price_non_negotiable_wholesale' => 'nullable|numeric|min:0',
            ]);

            // Utiliser price_normal comme prix principal si fourni, sinon utiliser price
            $mainPrice = $request->filled('price_normal') 
                ? (float) $request->input('price_normal') 
                : (float) $request->input('price');
            
            $dto = new CreateProductDTO(
                $shopId,
                $request->input('product_code'),
                $request->input('name'),
                $request->input('category_id'),
                $mainPrice,
                $request->input('currency'),
                (int) $request->input('minimum_stock'),
                $request->input('unit', 'UNITE'),
                $request->input('description') ?: null,
                null,
                null,
                $request->input('type_unite', 'UNITE'),
                (int) $request->input('quantite_par_unite', 1),
                $request->boolean('est_divisible', true),
                null, // imagePath sera défini après upload
                null, // imageType sera défini après upload
                $request->filled('price_normal') ? (float) $request->input('price_normal') : $mainPrice,
                $request->filled('price_reduced') ? (float) $request->input('price_reduced') : null,
                $request->filled('price_reduction_percent') ? (float) $request->input('price_reduction_percent') : null,
                $request->filled('price_non_negotiable') ? (float) $request->input('price_non_negotiable') : null,
                $request->filled('price_wholesale_normal') ? (float) $request->input('price_wholesale_normal') : null,
                $request->filled('price_wholesale_reduced') ? (float) $request->input('price_wholesale_reduced') : null,
                $request->filled('price_non_negotiable_wholesale') ? (float) $request->input('price_non_negotiable_wholesale') : null
            );

            $product = $this->createProductUseCase->execute($dto);

            // Obtenir le depot_id effectif selon les permissions
            $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);

            // Gérer l'upload d'image si fourni
            $imagePath = null;
            $imageType = null;
            
            if ($request->hasFile('image')) {
                try {
                    $imagePath = $this->imageService->upload($request->file('image'), $product->getId());
                    $imageType = $imagePath ? 'upload' : null;
                    Log::info('Product image uploaded successfully', [
                        'product_id' => $product->getId(),
                        'image_path' => $imagePath
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to upload product image', [
                        'product_id' => $product->getId(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Sauvegarder les données supplémentaires (image, prix multiples, et depot_id)
            $additionalData = [
                'depot_id' => $effectiveDepotId,
                'image_path' => $imagePath,
                'image_type' => $imageType,
                'price_normal' => $dto->priceNormal,
                'price_reduced' => $dto->priceReduced,
                'price_reduction_percent' => $dto->priceReductionPercent,
                'price_non_negotiable' => $dto->priceNonNegotiable,
                'price_wholesale_normal' => $dto->priceWholesaleNormal,
                'price_wholesale_reduced' => $dto->priceWholesaleReduced,
                'price_non_negotiable_wholesale' => $dto->priceNonNegotiableWholesale,
            ];
            
            // Filtrer seulement les valeurs null pour les prix, mais toujours inclure image_path et image_type même si null
            $additionalData = array_filter($additionalData, function($value, $key) {
                // Toujours inclure image_path et image_type même si null (pour permettre la suppression)
                if ($key === 'image_path' || $key === 'image_type') {
                    return true;
                }
                return $value !== null;
            }, ARRAY_FILTER_USE_BOTH);
            
            if ($this->productRepository instanceof EloquentProductRepository && !empty($additionalData)) {
                $this->productRepository->saveAdditionalData($product->getId(), $additionalData);
            }

            return redirect()->route('hardware.products')->with('success', 'Produit créé avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()])->withInput();
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
        
        // Vérifier l'accès au dépôt du produit et récupérer le modèle en une seule requête
        $model = ProductModel::with('category')->find($id);
        if (!$model) {
            abort(404, 'Produit introuvable.');
        }
        
        $query = ProductModel::where('id', $id);
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');
        if (!$query->exists()) {
            abort(404, 'Produit introuvable ou accès non autorisé à ce dépôt.');
        }
        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'product_code' => $product->getCode()->getValue(),
            'description' => $product->getDescription(),
            'category_id' => $product->getCategoryId(),
            'price_amount' => $product->getPrice()->getAmount(),
            'price_currency' => $product->getPrice()->getCurrency(),
            'image_path' => $model->image_path ?? null,
            'image_url' => $model && $model->image_path ? $this->imageService->getUrl($model->image_path, $model->image_type ?? 'upload') : null,
            'price_normal' => $model && $model->price_normal ? (float) $model->price_normal : null,
            'price_reduced' => $model && $model->price_reduced ? (float) $model->price_reduced : null,
            'price_reduction_percent' => $model && $model->price_reduction_percent ? (float) $model->price_reduction_percent : null,
            'price_non_negotiable' => $model && $model->price_non_negotiable ? (float) $model->price_non_negotiable : null,
            'price_wholesale_normal' => $model && $model->price_wholesale_normal ? (float) $model->price_wholesale_normal : null,
            'price_wholesale_reduced' => $model && $model->price_wholesale_reduced ? (float) $model->price_wholesale_reduced : null,
            'price_non_negotiable_wholesale' => $model && $model->price_non_negotiable_wholesale ? (float) $model->price_non_negotiable_wholesale : null,
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
        
        // Vérifier l'accès au dépôt du produit et récupérer le modèle en une seule requête
        $model = ProductModel::find($id);
        if (!$model) {
            abort(404, 'Produit introuvable.');
        }
        
        $query = ProductModel::where('id', $id);
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');
        if (!$query->exists()) {
            abort(404, 'Produit introuvable ou accès non autorisé à ce dépôt.');
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
            'image_path' => $model->image_path ?: null,
            'image_url' => $model->image_path ? $this->imageService->getUrl($model->image_path, $model->image_type ?: 'upload') : null,
            'price_normal' => $model->price_normal ? (float) $model->price_normal : null,
            'price_reduced' => $model->price_reduced ? (float) $model->price_reduced : null,
            'price_reduction_percent' => $model->price_reduction_percent ? (float) $model->price_reduction_percent : null,
            'price_non_negotiable' => $model->price_non_negotiable ? (float) $model->price_non_negotiable : null,
            'price_wholesale_normal' => $model->price_wholesale_normal ? (float) $model->price_wholesale_normal : null,
            'price_wholesale_reduced' => $model->price_wholesale_reduced ? (float) $model->price_wholesale_reduced : null,
            'price_non_negotiable_wholesale' => $model->price_non_negotiable_wholesale ? (float) $model->price_non_negotiable_wholesale : null,
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

    public function update(Request $request, string $id)
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            if (!$shopId) {
                return redirect()->back()->withErrors(['message' => 'Veuillez sélectionner un dépôt en haut.']);
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
                'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
                'remove_image' => 'nullable|boolean',
                'price_normal' => 'nullable|numeric|min:0',
                'price_reduced' => 'nullable|numeric|min:0',
                'price_reduction_percent' => 'nullable|numeric|min:0|max:100',
                'price_non_negotiable' => 'nullable|numeric|min:0',
                'price_wholesale_normal' => 'nullable|numeric|min:0',
                'price_wholesale_reduced' => 'nullable|numeric|min:0',
                'price_non_negotiable_wholesale' => 'nullable|numeric|min:0',
            ]);

            // Utiliser price_normal comme prix principal si fourni, sinon utiliser price
            $mainPrice = $request->filled('price_normal') 
                ? (float) $request->input('price_normal') 
                : (float) $request->input('price');
            
            $dto = new UpdateProductDTO(
                $id,
                $request->input('name'),
                $request->input('description'),
                $mainPrice,
                $request->input('currency'),
                $request->input('category_id'),
                $request->input('type_unite', 'UNITE'),
                (int) $request->input('quantite_par_unite', 1),
                $request->boolean('est_divisible', true),
                $request->filled('minimum_stock') ? (int) $request->input('minimum_stock') : null,
                null, // imagePath sera défini après upload
                null, // imageType sera défini après upload
                $request->filled('price_normal') ? (float) $request->input('price_normal') : $mainPrice,
                $request->filled('price_reduced') ? (float) $request->input('price_reduced') : null,
                $request->filled('price_reduction_percent') ? (float) $request->input('price_reduction_percent') : null,
                $request->filled('price_non_negotiable') ? (float) $request->input('price_non_negotiable') : null,
                $request->filled('price_wholesale_normal') ? (float) $request->input('price_wholesale_normal') : null,
                $request->filled('price_wholesale_reduced') ? (float) $request->input('price_wholesale_reduced') : null,
                $request->filled('price_non_negotiable_wholesale') ? (float) $request->input('price_non_negotiable_wholesale') : null
            );

            $this->updateProductUseCase->execute($dto);

            // Obtenir le depot_id effectif selon les permissions (préserver si pas de changement)
            $productModel = ProductModel::find($id);
            $effectiveDepotId = $productModel?->depot_id; // Préserver le dépôt existant par défaut
            
            // Si l'utilisateur demande explicitement un changement de dépôt
            if ($request->filled('depot_id')) {
                $requestedDepotId = (int) $request->input('depot_id');
                $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
                // Si l'utilisateur n'a pas accès au dépôt demandé, garder l'ancien
                if ($effectiveDepotId === null) {
                    $effectiveDepotId = $productModel?->depot_id;
                }
            }

            // Gérer l'upload/suppression d'image
            $imagePath = $productModel?->image_path;
            $imageType = $productModel && $productModel->image_type ? $productModel->image_type : 'upload';
            
            // Supprimer l'image si demandé
            if ($request->boolean('remove_image') && $imagePath) {
                $this->imageService->delete($imagePath);
                $imagePath = null;
                $imageType = null;
            }
            
            // Upload nouvelle image si fournie
            if ($request->hasFile('image')) {
                try {
                    // Supprimer l'ancienne image si elle existe
                    if ($imagePath) {
                        $this->imageService->delete($imagePath);
                    }
                    $imagePath = $this->imageService->upload($request->file('image'), $id);
                    $imageType = 'upload';
                } catch (\Exception $e) {
                    Log::warning('Failed to upload product image', [
                        'product_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Sauvegarder les données supplémentaires (image, prix multiples, et depot_id si modifié)
            $additionalData = [
                'depot_id' => $effectiveDepotId,
                'image_path' => $imagePath,
                'image_type' => $imageType,
                'price_normal' => $dto->priceNormal,
                'price_reduced' => $dto->priceReduced,
                'price_reduction_percent' => $dto->priceReductionPercent,
                'price_non_negotiable' => $dto->priceNonNegotiable,
                'price_wholesale_normal' => $dto->priceWholesaleNormal,
                'price_wholesale_reduced' => $dto->priceWholesaleReduced,
                'price_non_negotiable_wholesale' => $dto->priceNonNegotiableWholesale,
            ];
            
            // Filtrer seulement les valeurs null pour les prix, mais toujours inclure image_path et image_type même si null (pour permettre la suppression)
            $additionalData = array_filter($additionalData, function($value, $key) {
                // Toujours inclure image_path et image_type même si null (pour permettre la suppression)
                if ($key === 'image_path' || $key === 'image_type') {
                    return true;
                }
                return $value !== null;
            }, ARRAY_FILTER_USE_BOTH);
            
            if ($this->productRepository instanceof EloquentProductRepository && !empty($additionalData)) {
                $this->productRepository->saveAdditionalData($id, $additionalData);
            }

            return redirect()->route('hardware.products')->with('success', 'Produit mis à jour avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
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
            $this->productRepository->delete($id);
            return redirect()->route('hardware.products')->with('success', 'Produit supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function duplicateToDepot(Request $request, string $id)
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            
            $request->validate([
                'target_depot_id' => 'required|exists:depots,id',
            ]);
            
            $targetDepotId = (int) $request->input('target_depot_id');
            $shopId = $this->getShopId($request);
            $userModel = UserModel::query()->find($user->id);
            $isRoot = $userModel ? $userModel->isRoot() : false;

            $product = $this->productRepository->findById($id);
            if (!$product) {
                return redirect()->back()->withErrors(['message' => 'Produit non trouvé.']);
            }
            
            if ($shopId !== null && !$isRoot && $product->getShopId() !== $shopId) {
                return redirect()->back()->withErrors(['message' => 'Produit non trouvé.']);
            }

            $sourceModel = ProductModel::find($id);
            if (!$sourceModel) {
                return redirect()->back()->withErrors(['message' => 'Produit non trouvé.']);
            }

            $targetDepot = Depot::where('id', $targetDepotId)
                ->where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->first();
            
            if (!$targetDepot) {
                return redirect()->back()->withErrors(['message' => 'Dépôt cible non trouvé ou non actif.']);
            }

            if (!$isRoot && !$userModel->hasPermission('hardware.warehouse.view_all')) {
                $userDepotIds = $userModel->depots()->pluck('id')->toArray();
                if (!in_array($targetDepotId, $userDepotIds, true)) {
                    return redirect()->back()->withErrors(['message' => 'Vous n\'avez pas accès à ce dépôt.']);
                }
            }

            $targetCategories = $this->categoryRepository->findByShop($shopId, false);
            if (empty($targetCategories)) {
                return redirect()->back()->withErrors(['message' => 'Le dépôt cible n\'a aucune catégorie. Créez-en une avant de dupliquer.']);
            }

            $targetCategoryId = null;
            foreach ($targetCategories as $cat) {
                if ($cat->getId() === $product->getCategoryId()) {
                    $targetCategoryId = $cat->getId();
                    break;
                }
            }
            if (!$targetCategoryId) {
                $targetCategoryId = $targetCategories[0]->getId();
            }

            $sourceCode = $product->getCode()->getValue();
            $newCode = $sourceCode . '-CP' . strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 4));
            while ($this->productRepository->existsByCode($newCode)) {
                $newCode = $sourceCode . '-CP' . strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 4));
            }

            $price = $product->getPrice();
            $dto = new CreateProductDTO(
                $shopId,
                $newCode,
                $product->getName(),
                $targetCategoryId,
                (float) $price->getAmount(),
                $price->getCurrency(),
                0,
                $product->getTypeUnite()->getValue(),
                $product->getDescription() ?: null,
                null,
                null,
                $product->getTypeUnite()->getValue(),
                $product->getQuantiteParUnite(),
                $product->estDivisible(),
                null,
                null,
                $sourceModel->price_normal ? (float) $sourceModel->price_normal : null,
                $sourceModel->price_reduced ? (float) $sourceModel->price_reduced : null,
                $sourceModel->price_reduction_percent ? (float) $sourceModel->price_reduction_percent : null,
                $sourceModel->price_non_negotiable ? (float) $sourceModel->price_non_negotiable : null,
                $sourceModel->price_wholesale_normal ? (float) $sourceModel->price_wholesale_normal : null,
                $sourceModel->price_wholesale_reduced ? (float) $sourceModel->price_wholesale_reduced : null,
                $sourceModel->price_non_negotiable_wholesale ? (float) $sourceModel->price_non_negotiable_wholesale : null
            );

            $previousDepotId = $request->session()->get('current_depot_id');
            $request->session()->put('current_depot_id', $targetDepotId);
            
            try {
                $newProduct = $this->createProductUseCase->execute($dto);
                
                if ($sourceModel->image_path) {
                    try {
                        $sourceImagePath = 'hardware/products/' . $sourceModel->image_path;
                        
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($sourceImagePath)) {
                            $extension = pathinfo($sourceModel->image_path, PATHINFO_EXTENSION);
                            $newFilename = $newProduct->getId() . '_' . \Illuminate\Support\Str::random(10) . '.' . $extension;
                            $newImagePath = 'hardware/products/' . $newFilename;
                            
                            \Illuminate\Support\Facades\Storage::disk('public')->copy($sourceImagePath, $newImagePath);
                            $imagePath = $newFilename;
                        } else {
                            $imagePath = null;
                        }
                        
                        $additionalData = [
                            'depot_id' => $targetDepotId,
                            'image_path' => $imagePath,
                            'image_type' => $sourceModel->image_type ?: 'upload',
                            'price_normal' => $dto->priceNormal,
                            'price_reduced' => $dto->priceReduced,
                            'price_reduction_percent' => $dto->priceReductionPercent,
                            'price_non_negotiable' => $dto->priceNonNegotiable,
                            'price_wholesale_normal' => $dto->priceWholesaleNormal,
                            'price_wholesale_reduced' => $dto->priceWholesaleReduced,
                            'price_non_negotiable_wholesale' => $dto->priceNonNegotiableWholesale,
                        ];
                        
                        $additionalData = array_filter($additionalData, function($value, $key) {
                            if ($key === 'image_path' || $key === 'image_type') {
                                return true;
                            }
                            return $value !== null;
                        }, ARRAY_FILTER_USE_BOTH);
                        
                        if ($this->productRepository instanceof EloquentProductRepository && !empty($additionalData)) {
                            $this->productRepository->saveAdditionalData($newProduct->getId(), $additionalData);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to copy product image during duplication', [
                            'product_id' => $newProduct->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $additionalData = [
                        'depot_id' => $targetDepotId,
                        'price_normal' => $dto->priceNormal,
                        'price_reduced' => $dto->priceReduced,
                        'price_reduction_percent' => $dto->priceReductionPercent,
                        'price_non_negotiable' => $dto->priceNonNegotiable,
                        'price_wholesale_normal' => $dto->priceWholesaleNormal,
                        'price_wholesale_reduced' => $dto->priceWholesaleReduced,
                        'price_non_negotiable_wholesale' => $dto->priceNonNegotiableWholesale,
                    ];
                    
                    $additionalData = array_filter($additionalData, function($value) {
                        return $value !== null;
                    });
                    
                    if ($this->productRepository instanceof EloquentProductRepository && !empty($additionalData)) {
                        $this->productRepository->saveAdditionalData($newProduct->getId(), $additionalData);
                    }
                }
            } finally {
                if ($previousDepotId !== null) {
                    $request->session()->put('current_depot_id', $previousDepotId);
                } else {
                    $request->session()->forget('current_depot_id');
                }
            }

            return redirect()->route('hardware.products')->with('success', 'Produit dupliqué vers le dépôt "' . $targetDepot->name . '" avec succès.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error duplicating product to depot', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['message' => 'Erreur lors de la duplication : ' . $e->getMessage()]);
        }
    }
}
