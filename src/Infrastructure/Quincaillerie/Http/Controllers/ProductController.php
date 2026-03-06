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
use App\Http\Middleware\HandleInertiaRequests;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

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
        Log::info('Hardware ProductController@index called', [
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'method' => $request->method(),
        ]);
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
                'barcode' => $model->barcode ?? null,
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

        $depotsData = HandleInertiaRequests::getDepotsForRequest($request);
        return Inertia::render('Hardware/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status']),
            'canImport' =>
                $isRoot
                || ($userModel && method_exists($userModel, 'hasPermission')
                    && ($userModel->hasPermission('hardware.product.import') || $userModel->hasPermission('hardware.product.manage'))
                ),
            'depots' => $depotsData['depots'],
            'currentDepot' => $depotsData['currentDepot'],
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        if (!$shopId) {
            abort(403, 'Veuillez sélectionner un dépôt en haut.');
        }
        $categories = $this->categoryRepository->findByShop($shopId, true);
        $depotsData = HandleInertiaRequests::getDepotsForRequest($request);
        return Inertia::render('Hardware/Products/Create', [
            'categories' => $categories,
            'depots' => $depotsData['depots'],
            'currentDepot' => $depotsData['currentDepot'],
        ]);
    }

    public function store(Request $request)
    {
        // Validation en dehors du try pour laisser Laravel/ Inertia gérer les erreurs champ par champ
        $request->validate([
            'name' => 'required|string|max:255',
            'product_code' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:quincaillerie_categories,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'minimum_stock' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'type_unite' => 'required|string|in:PIECE,LOT,METRE,KG,LITRE,BOITE,CARTON,UNITE',
            'quantite_par_unite' => 'required|integer|min:1',
            'est_divisible' => 'boolean',
            // 8 Mo max pour supporter les photos récentes
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
            'price_normal' => 'nullable|numeric|min:0',
            'price_reduced' => 'nullable|numeric|min:0',
            'price_reduction_percent' => 'nullable|numeric|min:0|max:100',
            'price_non_negotiable' => 'nullable|numeric|min:0',
            'price_wholesale_normal' => 'nullable|numeric|min:0',
            'price_wholesale_reduced' => 'nullable|numeric|min:0',
            'price_non_negotiable_wholesale' => 'nullable|numeric|min:0',
        ]);

        try {
            // Debug: vérifier la présence du fichier côté backend
            Log::debug('Hardware ProductController@store - incoming files', [
                'has_file_image' => $request->hasFile('image'),
                'all_files_keys' => array_keys($request->allFiles()),
                'image_is_instance' => $request->file('image') instanceof \Illuminate\Http\UploadedFile,
                'image_error' => $request->file('image')?->getError(),
                'image_original_name' => $request->file('image')?->getClientOriginalName(),
                'content_type' => $request->header('Content-Type'),
            ]);

            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $this->getShopId($request);
            if (!$shopId) {
                return redirect()->back()->withErrors(['message' => 'Veuillez sélectionner un dépôt en haut.']);
            }

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
                $request->filled('price_non_negotiable_wholesale') ? (float) $request->input('price_non_negotiable_wholesale') : null,
                $request->input('barcode') ?: null
            );

            $product = $this->createProductUseCase->execute($dto);

            // Obtenir le depot_id effectif selon les permissions
            $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
            // Si le service ne renvoie rien, forcer le dépôt courant de la session
            if ($effectiveDepotId === null) {
                $sessionDepotId = $request->session()->get('current_depot_id');
                if ($sessionDepotId) {
                    $effectiveDepotId = (int) $sessionDepotId;
                }
            }

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
                    return redirect()->back()->withErrors(['image' => $e->getMessage()])->withInput();
                }
            }

            // Sauvegarder les données supplémentaires (image, prix multiples, depot_id, et barcode)
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
                'barcode' => $dto->barcode,
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
            'barcode' => $model->barcode ?? null,
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
            'barcode' => $model->barcode ?? null,
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

            // Debug: vérifier la présence du fichier côté backend lors de la mise à jour
            Log::debug('Hardware ProductController@update - incoming files', [
                'product_id' => $id,
                'has_file_image' => $request->hasFile('image'),
                'all_files_keys' => array_keys($request->allFiles()),
                'image_is_instance' => $request->file('image') instanceof \Illuminate\Http\UploadedFile,
                'image_error' => $request->file('image')?->getError(),
                'image_original_name' => $request->file('image')?->getClientOriginalName(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
            ]);

            // Charger le produit existant pour pouvoir réutiliser ses valeurs par défaut
            $existingProduct = $this->productRepository->findById($id);
            if (!$existingProduct || $existingProduct->getShopId() !== $shopId) {
                abort(404, 'Produit introuvable.');
            }

            // Validation souple : les champs de base sont optionnels ici, on retombe sur les valeurs existantes si absents
            $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'category_id' => 'nullable|exists:quincaillerie_categories,id',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'minimum_stock' => 'nullable|integer|min:0',
                'type_unite' => 'nullable|string|in:PIECE,LOT,METRE,KG,LITRE,BOITE,CARTON,UNITE',
                'quantite_par_unite' => 'nullable|integer|min:1',
                'est_divisible' => 'sometimes|boolean',
                // 8 Mo max pour supporter les photos récentes
                'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
                'remove_image' => 'nullable|boolean',
                'price_normal' => 'nullable|numeric|min:0',
                'price_reduced' => 'nullable|numeric|min:0',
                'price_reduction_percent' => 'nullable|numeric|min:0|max:100',
                'price_non_negotiable' => 'nullable|numeric|min:0',
                'price_wholesale_normal' => 'nullable|numeric|min:0',
                'price_wholesale_reduced' => 'nullable|numeric|min:0',
                'price_non_negotiable_wholesale' => 'nullable|numeric|min:0',
                'barcode' => 'nullable|string|max:255',
            ]);

            // Valeurs de base : utiliser la requête si fournie, sinon garder la valeur existante
            $name = $request->filled('name')
                ? (string) $request->input('name')
                : $existingProduct->getName();

            $description = $request->filled('description')
                ? (string) $request->input('description')
                : $existingProduct->getDescription();

            $categoryId = $request->filled('category_id')
                ? (string) $request->input('category_id')
                : $existingProduct->getCategoryId();

            $currency = $request->filled('currency')
                ? (string) $request->input('currency')
                : $existingProduct->getPrice()->getCurrency();

            $priceInput = $request->input('price');
            $basePrice = ($priceInput !== null && $priceInput !== '')
                ? (float) $priceInput
                : $existingProduct->getPrice()->getAmount();

            $typeUnite = $request->filled('type_unite')
                ? (string) $request->input('type_unite')
                : $existingProduct->getTypeUnite()->getValue();

            $quantiteParUnite = $request->filled('quantite_par_unite')
                ? (int) $request->input('quantite_par_unite')
                : $existingProduct->getQuantiteParUnite();

            $estDivisible = $request->has('est_divisible')
                ? $request->boolean('est_divisible')
                : $existingProduct->estDivisible();

            $minimumStock = $request->filled('minimum_stock')
                ? (int) $request->input('minimum_stock')
                : $existingProduct->getMinimumStock()->getValue();

            // Utiliser price_normal comme prix principal si fourni, sinon utiliser price
            $mainPrice = $request->filled('price_normal')
                ? (float) $request->input('price_normal')
                : $basePrice;
            
            $dto = new UpdateProductDTO(
                $id,
                $name,
                $description,
                $mainPrice,
                $currency,
                $categoryId,
                $typeUnite ?: 'UNITE',
                $quantiteParUnite > 0 ? $quantiteParUnite : 1,
                $estDivisible,
                $minimumStock,
                null, // imagePath sera défini après upload
                null, // imageType sera défini après upload
                $request->filled('price_normal') ? (float) $request->input('price_normal') : $mainPrice,
                $request->filled('price_reduced') ? (float) $request->input('price_reduced') : null,
                $request->filled('price_reduction_percent') ? (float) $request->input('price_reduction_percent') : null,
                $request->filled('price_non_negotiable') ? (float) $request->input('price_non_negotiable') : null,
                $request->filled('price_wholesale_normal') ? (float) $request->input('price_wholesale_normal') : null,
                $request->filled('price_wholesale_reduced') ? (float) $request->input('price_wholesale_reduced') : null,
                $request->filled('price_non_negotiable_wholesale') ? (float) $request->input('price_non_negotiable_wholesale') : null,
                $request->input('barcode') ?: null
            );

            $this->updateProductUseCase->execute($dto);

            // Obtenir le depot_id effectif
            $productModel = ProductModel::find($id);
            // Par défaut, utiliser le dépôt courant de la session s'il existe
            $effectiveDepotId = null;
            $sessionDepotId = $request->session()->get('current_depot_id');
            if ($sessionDepotId) {
                $effectiveDepotId = (int) $sessionDepotId;
            } else {
                // Sinon, préserver le dépôt existant
                $effectiveDepotId = $productModel?->depot_id;
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
                    return redirect()->back()->withErrors(['image' => $e->getMessage()])->withInput();
                }
            }

            // Sauvegarder les données supplémentaires (image, prix multiples, depot_id si modifié, et barcode)
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
                'barcode' => $dto->barcode,
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

    /**
     * Modèle Excel pour l'import de produits Hardware.
     */
    public function importTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Produits Hardware');

        $headers = [
            'nom',
            'code',
            'categorie_id',
            'prix',
            'unite',
            'description',
            'stock_minimum',
            'type_unite',
            'quantite_par_unite',
            'est_divisible',
            'prix_normal',
            'prix_reduit',
            'prix_reduction_pourcent',
            'prix_non_negociable',
            'prix_gros_normal',
            'prix_gros_reduit',
            'prix_gros_non_negociable',
            'barcode',
            'currency',
        ];

        $colIndex = 1;
        foreach ($headers as $header) {
            $columnLetter = chr(ord('A') + $colIndex - 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            $colIndex++;
        }

        // Exemple de ligne
        $sheet->setCellValue('A2', 'Marteau de charpentier');
        $sheet->setCellValue('B2', 'MART-001');
        $sheet->setCellValue('C2', 'OUTILS'); // ID ou nom de catégorie
        $sheet->setCellValue('D2', 10_00); // 10.00
        $sheet->setCellValue('E2', 'PIECE');
        $sheet->setCellValue('F2', 'Marteau robuste pour travaux de charpente');
        $sheet->setCellValue('G2', 5); // stock_minimum
        $sheet->setCellValue('H2', 'PIECE');
        $sheet->setCellValue('I2', 1);
        $sheet->setCellValue('J2', 'oui');
        $sheet->setCellValue('K2', 10_00);
        $sheet->setCellValue('L2', 9_50);
        $sheet->setCellValue('M2', 5);
        $sheet->setCellValue('N2', 9_00);
        $sheet->setCellValue('O2', 8_50);
        $sheet->setCellValue('P2', 8_00);
        $sheet->setCellValue('Q2', '1234567890123');
        $sheet->setCellValue('R2', 'USD');

        $filename = 'modele_import_produits_hardware_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aperçu de l'import de produits Hardware (validation sans insertion).
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
        if (!$shopId) {
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
            Log::error('Hardware Product import preview: parse error', ['error' => $e->getMessage()]);
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

        $required = ['nom', 'code', 'categorie_id', 'prix', 'unite'];
        $missing = array_diff($required, $headerRow);
        if (!empty($missing)) {
            return response()->json([
                'total' => count($dataRows),
                'valid' => 0,
                'invalid' => count($dataRows),
                'errors' => [
                    [
                        'line' => 1,
                        'field' => null,
                        'message' => 'Colonnes obligatoires manquantes : ' . implode(', ', $missing),
                    ],
                ],
                'sample' => [
                    'header' => $rows[0] ?? [],
                    'rows' => array_slice($dataRows, 0, 20),
                ],
            ]);
        }

        $existingProducts = ProductModel::query()
            ->where('shop_id', $shopId)
            ->pluck('code')
            ->map(fn ($c) => mb_strtolower((string) $c))
            ->all();
        $existingCodes = array_fill_keys($existingProducts, true);

        $categoryQuery = CategoryModel::query()->where('shop_id', $shopId);
        $existingCategories = $categoryQuery->get(['id', 'name']);

        $seenCodes = [];
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
            }

            $code = $rowAssoc['code'] ?? '';
            if ($code === '') {
                $lineErrors[] = 'Code obligatoire.';
            } else {
                $lowerCode = mb_strtolower($code);
                if (isset($existingCodes[$lowerCode])) {
                    $lineErrors[] = "Code déjà existant : {$code}.";
                }
                if (isset($seenCodes[$lowerCode])) {
                    $lineErrors[] = "Code en double dans le fichier : {$code}.";
                }
            }

            $catRaw = $rowAssoc['categorie_id'] ?? '';
            if ($catRaw === '') {
                $lineErrors[] = 'Categorie_id obligatoire.';
            } else {
                $category = $existingCategories->firstWhere('id', $catRaw);
                if (!$category) {
                    $category = $existingCategories->firstWhere('name', $catRaw);
                }
                if (!$category) {
                    $lineErrors[] = "Catégorie introuvable : {$catRaw}.";
                }
            }

            $priceRaw = $rowAssoc['prix'] ?? '';
            if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
                $lineErrors[] = 'Prix invalide.';
            }

            $unit = $rowAssoc['unite'] ?? '';
            if ($unit === '') {
                $lineErrors[] = 'Unité obligatoire.';
            }

            $typeUnite = $rowAssoc['type_unite'] ?? '';
            if ($typeUnite !== '') {
                $allowedTypes = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];
                if (!in_array(strtoupper($typeUnite), $allowedTypes, true)) {
                    $lineErrors[] = "Type d'unité invalide : {$typeUnite}.";
                }
            }

            $qtyRaw = $rowAssoc['quantite_par_unite'] ?? '';
            if ($qtyRaw !== '') {
                if (!ctype_digit($qtyRaw) || (int) $qtyRaw < 1) {
                    $lineErrors[] = 'Quantité par unité doit être un entier >= 1.';
                }
            }

            $minStockRaw = $rowAssoc['stock_minimum'] ?? '';
            if ($minStockRaw !== '') {
                if (!ctype_digit((string) $minStockRaw) || (int) $minStockRaw < 0) {
                    $lineErrors[] = 'Stock minimum doit être un entier >= 0.';
                }
            }

            $estDivisibleRaw = $rowAssoc['est_divisible'] ?? '';
            if ($estDivisibleRaw !== '') {
                $val = mb_strtolower($estDivisibleRaw);
                $allowed = ['oui', 'non', 'yes', 'no', '1', '0', 'true', 'false'];
                if (!in_array($val, $allowed, true)) {
                    $lineErrors[] = "Valeur 'est_divisible' invalide (utiliser oui/non) : {$estDivisibleRaw}.";
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
                if ($code !== '') {
                    $seenCodes[mb_strtolower($code)] = true;
                }
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
     * Import effectif des produits Hardware.
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
        if (!$shopId) {
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
            Log::error('Hardware Product import: parse error', ['error' => $e->getMessage()]);
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

        $required = ['nom', 'code', 'categorie_id', 'prix', 'unite'];
        $missing = array_diff($required, $headerRow);
        if (!empty($missing)) {
            $failed = count($dataRows);
            return response()->json([
                'message' => 'Colonnes obligatoires manquantes : ' . implode(', ', $missing),
                'success' => 0,
                'failed' => $failed,
                'total' => $failed,
                'errors' => ["Ligne 1: Colonnes obligatoires manquantes : " . implode(', ', $missing)],
            ], 422);
        }

        $existingProducts = ProductModel::query()
            ->where('shop_id', $shopId)
            ->pluck('code')
            ->map(fn ($c) => mb_strtolower((string) $c))
            ->all();
        $existingCodes = array_fill_keys($existingProducts, true);

        $categoryQuery = CategoryModel::query()->where('shop_id', $shopId);
        $existingCategories = $categoryQuery->get(['id', 'name']);

        $seenCodes = [];
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

            $code = $rowAssoc['code'] ?? '';
            if ($code === '') {
                $lineErrors[] = 'Code obligatoire.';
            } else {
                $lowerCode = mb_strtolower($code);
                if (isset($existingCodes[$lowerCode]) || isset($seenCodes[$lowerCode])) {
                    $lineErrors[] = "Code déjà existant ou dupliqué : {$code}.";
                }
            }

            $catRaw = $rowAssoc['categorie_id'] ?? '';
            $categoryId = null;
            if ($catRaw === '') {
                $lineErrors[] = 'Categorie_id obligatoire.';
            } else {
                $category = $existingCategories->firstWhere('id', $catRaw);
                if (!$category) {
                    $category = $existingCategories->firstWhere('name', $catRaw);
                }
                if (!$category) {
                    $lineErrors[] = "Catégorie introuvable : {$catRaw}.";
                } else {
                    $categoryId = $category->id;
                }
            }

            $priceRaw = $rowAssoc['prix'] ?? '';
            $price = null;
            if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
                $lineErrors[] = 'Prix invalide.';
            } else {
                $price = (float) $priceRaw;
            }

            $unit = $rowAssoc['unite'] ?? '';
            if ($unit === '') {
                $lineErrors[] = 'Unité obligatoire.';
            }

            $typeUnite = $rowAssoc['type_unite'] ?? 'UNITE';
            $allowedTypes = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];
            $typeUniteUpper = strtoupper($typeUnite);
            if (!in_array($typeUniteUpper, $allowedTypes, true)) {
                $lineErrors[] = "Type d'unité invalide : {$typeUnite}.";
            }

            $qtyRaw = $rowAssoc['quantite_par_unite'] ?? '1';
            $qty = 1;
            if ($qtyRaw !== '') {
                if (!ctype_digit((string) $qtyRaw) || (int) $qtyRaw < 1) {
                    $lineErrors[] = 'Quantité par unité doit être un entier >= 1.';
                } else {
                    $qty = (int) $qtyRaw;
                }
            }

            $minStockRaw = $rowAssoc['stock_minimum'] ?? '0';
            $minStock = 0;
            if ($minStockRaw !== '') {
                if (!ctype_digit((string) $minStockRaw) || (int) $minStockRaw < 0) {
                    $lineErrors[] = 'Stock minimum doit être un entier >= 0.';
                } else {
                    $minStock = (int) $minStockRaw;
                }
            }

            $estDivisibleRaw = $rowAssoc['est_divisible'] ?? 'oui';
            $estDivisible = true;
            if ($estDivisibleRaw !== '') {
                $val = mb_strtolower($estDivisibleRaw);
                $trueVals = ['oui', 'yes', '1', 'true'];
                $falseVals = ['non', 'no', '0', 'false'];
                if (in_array($val, $trueVals, true)) {
                    $estDivisible = true;
                } elseif (in_array($val, $falseVals, true)) {
                    $estDivisible = false;
                } else {
                    $lineErrors[] = "Valeur 'est_divisible' invalide (utiliser oui/non) : {$estDivisibleRaw}.";
                }
            }

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $currency = $rowAssoc['currency'] !== '' ? strtoupper($rowAssoc['currency']) : 'USD';
                $priceNormal = $rowAssoc['prix_normal'] !== '' ? (float) $rowAssoc['prix_normal'] : $price;
                $priceReduced = $rowAssoc['prix_reduit'] !== '' ? (float) $rowAssoc['prix_reduit'] : null;
                $priceReductionPercent = $rowAssoc['prix_reduction_pourcent'] !== '' ? (float) $rowAssoc['prix_reduction_pourcent'] : null;
                $priceNonNegotiable = $rowAssoc['prix_non_negociable'] !== '' ? (float) $rowAssoc['prix_non_negociable'] : null;
                $priceWholesaleNormal = $rowAssoc['prix_gros_normal'] !== '' ? (float) $rowAssoc['prix_gros_normal'] : null;
                $priceWholesaleReduced = $rowAssoc['prix_gros_reduit'] !== '' ? (float) $rowAssoc['prix_gros_reduit'] : null;
                $priceWholesaleNonNeg = $rowAssoc['prix_gros_non_negociable'] !== '' ? (float) $rowAssoc['prix_gros_non_negociable'] : null;

                $dto = new CreateProductDTO(
                    $shopId,
                    $code,
                    $name,
                    $categoryId,
                    $price,
                    $currency,
                    $minStock,
                    $unit,
                    $rowAssoc['description'] !== '' ? $rowAssoc['description'] : null,
                    null,
                    null,
                    $typeUniteUpper,
                    $qty,
                    $estDivisible,
                    null,
                    null,
                    $priceNormal,
                    $priceReduced,
                    $priceReductionPercent,
                    $priceNonNegotiable,
                    $priceWholesaleNormal,
                    $priceWholesaleReduced,
                    $priceWholesaleNonNeg,
                    $rowAssoc['barcode'] !== '' ? $rowAssoc['barcode'] : null
                );

                $product = $this->createProductUseCase->execute($dto);

                // Assigner depot_id
                $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
                if ($this->productRepository instanceof EloquentProductRepository) {
                    $additionalData = [
                        'depot_id' => $effectiveDepotId,
                        'price_normal' => $dto->priceNormal,
                        'price_reduced' => $dto->priceReduced,
                        'price_reduction_percent' => $dto->priceReductionPercent,
                        'price_non_negociable' => $dto->priceNonNegotiable,
                        'price_wholesale_normal' => $dto->priceWholesaleNormal,
                        'price_wholesale_reduced' => $dto->priceWholesaleReduced,
                        'price_non_negociable_wholesale' => $dto->priceNonNegotiableWholesale,
                        'barcode' => $dto->barcode,
                    ];
                    $additionalData = array_filter($additionalData, function ($value, $key) {
                        if ($key === 'depot_id') {
                            return $value !== null;
                        }
                        return $value !== null;
                    }, ARRAY_FILTER_USE_BOTH);

                    if (!empty($additionalData)) {
                        $this->productRepository->saveAdditionalData($product->getId(), $additionalData);
                    }
                }

                if ($code !== '') {
                    $seenCodes[mb_strtolower($code)] = true;
                }

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import produits Hardware terminé.',
            'success' => $success,
            'failed' => $failed,
            'total' => $total,
            'errors' => $errors,
        ]);
    }
}
