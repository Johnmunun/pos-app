<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Src\Application\Pharmacy\UseCases\Product\CreateProductUseCase;
use Src\Application\Pharmacy\UseCases\Product\UpdateProductUseCase;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\CreateProductDTO;
use Src\Application\Pharmacy\DTO\UpdateProductDTO;
use Src\Application\Pharmacy\DTO\UpdateStockDTO;
use Src\Infrastructure\Pharmacy\Services\ProductImageService;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;

class ProductController
{
    public function __construct(
        private CreateProductUseCase $createProductUseCase,
        private UpdateProductUseCase $updateProductUseCase,
        private UpdateStockUseCase $updateStockUseCase,
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private ProductImageService $imageService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // ROOT users can access all shops, others need shop_id
        // For now, use tenant_id as shop_id if shop_id doesn't exist
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        // Ensure UserModel has isRoot() method
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;
        
        // ROOT users can access even without shop_id (they can see all products)
        // For non-ROOT users, shop_id is required
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        
        // For ROOT users without shop_id, show all products or redirect to shop selection
        // For now, if ROOT has no shop_id, we'll use a default or show empty list
        if ($isRoot && !$shopId) {
            $shopId = null; // ROOT can see all, we'll handle this in the query
        }
        
        // Get products for current shop
        // ROOT users without shop_id can see all products
        if ($isRoot && !$shopId) {
            $productModels = \Src\Infrastructure\Pharmacy\Models\ProductModel::with('category')
                ->orderBy('name')
                ->get();
        } else {
            $productEntities = $this->productRepository->findByShop($shopId);
            // Convert entities to arrays for Inertia
            // Use ProductModel directly for better serialization
            $productModels = \Src\Infrastructure\Pharmacy\Models\ProductModel::where('shop_id', $shopId)
                ->with('category')
                ->orderBy('name')
                ->get();
        }
        
        $products = $productModels->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'product_code' => $model->code ?? $model->product_code ?? '',
                'description' => $model->description ?? '',
                'category_id' => $model->category_id,
                'price_amount' => (float) ($model->price_amount ?? 0),
                'price_currency' => $model->price_currency ?? 'USD',
                'cost' => isset($model->cost_amount) ? (float) $model->cost_amount : null,
                'minimum_stock' => (int) ($model->minimum_stock ?? 0),
                'current_stock' => (int) ($model->stock ?? 0),
                'unit' => $model->unit ?? '',
                'medicine_type' => $model->type ?? null,
                'dosage' => $model->dosage ?? null,
                'prescription_required' => (bool) ($model->requires_prescription ?? false),
                'manufacturer' => $model->manufacturer ?? null,
                'supplier_id' => $model->supplier_id ?? null,
                'is_active' => (bool) ($model->is_active ?? true),
                'image_path' => $model->image_path ?? null,
                'image_type' => $model->image_type ?? 'upload',
                'image_url' => $this->imageService->getUrlFromPath($model->image_path, $model->image_type ?? 'upload'),
                'category' => $model->category ? [
                    'id' => $model->category->id,
                    'name' => $model->category->name,
                ] : null,
            ];
        })->toArray();
        
        // Get categories for filters - use model directly for serialization
        // ROOT users without shop_id can see all categories
        if ($isRoot && !$shopId) {
            $categoryModels = \Src\Infrastructure\Pharmacy\Models\CategoryModel::where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $categoryModels = \Src\Infrastructure\Pharmacy\Models\CategoryModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        
        $categories = $categoryModels->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'description' => $model->description,
            ];
        })->toArray();
        
        return Inertia::render('Pharmacy/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'type'])
        ]);
    }

    public function create(): Response
    {
        $categories = $this->categoryRepository->findByShop(
            request()->user()->shop_id, 
            true
        );
        
        return Inertia::render('Pharmacy/Products/Create', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            
            if (!$shopId) {
                return response()->json([
                    'message' => 'Shop ID not found. Please contact administrator.'
                ], 403);
            }
            
            $request->validate([
                'name' => 'required|string|max:255',
                'product_code' => 'required|string|max:50|unique:pharmacy_products,code',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:pharmacy_categories,id',
                'price' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'cost' => 'nullable|numeric|min:0',
                'minimum_stock' => 'required|integer|min:0',
                'unit' => 'required|string|max:50',
                'medicine_type' => 'nullable|string|max:50',
                'dosage' => 'nullable|string|max:100',
                'prescription_required' => 'boolean',
                'manufacturer' => 'nullable|string|max:255',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
                'image_url' => 'nullable|url|max:500',
            ]);

            $dto = new CreateProductDTO(
                $shopId,
                $request->input('product_code'),
                $request->input('name'),
                $request->input('description') ?: null,
                $request->input('category_id'),
                (float) $request->input('price'),
                $request->input('currency'),
                $request->filled('cost') ? (float) $request->input('cost') : null,
                (int) $request->input('minimum_stock'),
                $request->input('unit'),
                $request->filled('medicine_type') ? $request->input('medicine_type') : null,
                $request->input('dosage') ?: null,
                $request->boolean('prescription_required', false),
                $request->input('manufacturer') ?: null,
                $request->input('supplier_id') ?: null
            );

            $product = $this->createProductUseCase->execute($dto);

            // Gérer l'upload d'image si fourni
            $imagePath = null;
            $imageType = 'upload';
            
            if ($request->hasFile('image')) {
                try {
                    $productImage = $this->imageService->upload($request->file('image'), $product->getId());
                    $imagePath = $productImage->getPath();
                    $imageType = $productImage->getType();
                } catch (\Exception $e) {
                    // Si l'upload échoue, on continue sans image
                    \Log::warning('Failed to upload product image', [
                        'product_id' => $product->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($request->filled('image_url')) {
                $imagePath = $request->input('image_url');
                $imageType = 'url';
            }

            // Mettre à jour le produit avec l'image
            if ($imagePath) {
                \Src\Infrastructure\Pharmacy\Models\ProductModel::where('id', $product->getId())
                    ->update([
                        'image_path' => $imagePath,
                        'image_type' => $imageType
                    ]);
            }

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(string $id): Response
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            abort(404);
        }

        // Get related batches
        $batches = []; // $this->batchRepository->findByProduct($id);
        
        return Inertia::render('Pharmacy/Products/Show', [
            'product' => $product,
            'batches' => $batches
        ]);
    }

    public function edit(string $id): Response
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            abort(404);
        }

        $categories = $this->categoryRepository->findByShop(
            request()->user()->shop_id, 
            true
        );

        return Inertia::render('Pharmacy/Products/Edit', [
            'product' => $product,
            'categories' => $categories
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'product_code' => 'sometimes|string|max:50|unique:pharmacy_products,code,' . $id,
                'description' => 'nullable|string',
                'category_id' => 'sometimes|exists:pharmacy_categories,id',
                'price' => 'sometimes|numeric|min:0',
                'currency' => 'sometimes|string|size:3',
                'cost' => 'nullable|numeric|min:0',
                'minimum_stock' => 'sometimes|integer|min:0',
                'unit' => 'sometimes|string|max:50',
                'medicine_type' => 'nullable|string|max:50',
                'dosage' => 'nullable|string|max:100',
                'prescription_required' => 'boolean',
                'manufacturer' => 'nullable|string|max:255',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'is_active' => 'boolean',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
                'image_url' => 'nullable|url|max:500',
                'remove_image' => 'nullable|boolean'
            ]);

            $dto = new UpdateProductDTO(
                $request->user()->shop_id,
                $request->input('name'),
                $request->input('product_code'),
                $request->input('description'),
                $request->input('category_id'),
                $request->input('price') !== null ? (float) $request->input('price') : null,
                $request->input('currency'),
                $request->input('cost') !== null ? (float) $request->input('cost') : null,
                $request->input('minimum_stock') !== null ? (int) $request->input('minimum_stock') : null,
                $request->input('unit'),
                $request->filled('medicine_type') ? $request->input('medicine_type') : null,
                $request->filled('dosage') ? $request->input('dosage') : null,
                $request->boolean('prescription_required', false),
                $request->input('manufacturer'),
                $request->input('supplier_id'),
                $request->input('is_active')
            );

            $product = $this->updateProductUseCase->execute($id, $dto);

            // Gérer l'upload/suppression d'image
            $productModel = \Src\Infrastructure\Pharmacy\Models\ProductModel::find($id);
            if ($productModel) {
                // Supprimer l'image existante si demandé
                if ($request->boolean('remove_image')) {
                    if ($productModel->image_path) {
                        $this->imageService->deleteByPath($productModel->image_path, $productModel->image_type ?? 'upload');
                    }
                    $productModel->update([
                        'image_path' => null,
                        'image_type' => 'upload'
                    ]);
                }
                // Upload nouvelle image
                elseif ($request->hasFile('image')) {
                    try {
                        // Supprimer l'ancienne image si elle existe
                        if ($productModel->image_path && ($productModel->image_type ?? 'upload') === 'upload') {
                            $this->imageService->deleteByPath($productModel->image_path, 'upload');
                        }
                        
                        $productImage = $this->imageService->upload($request->file('image'), $id);
                        $productModel->update([
                            'image_path' => $productImage->getPath(),
                            'image_type' => $productImage->getType()
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to upload product image', [
                            'product_id' => $id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                // Mettre à jour avec URL
                elseif ($request->filled('image_url')) {
                    // Supprimer l'ancienne image si c'était un upload
                    if ($productModel->image_path && ($productModel->image_type ?? 'upload') === 'upload') {
                        $this->imageService->deleteByPath($productModel->image_path, 'upload');
                    }
                    
                    $productModel->update([
                        'image_path' => $request->input('image_url'),
                        'image_type' => 'url'
                    ]);
                }
            }

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $product = $this->productRepository->findById($id);
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found'
                ], 404);
            }

            // Check if product has stock
            if ($product->getStock()->getValue() > 0) {
                return response()->json([
                    'message' => 'Cannot delete product with existing stock'
                ], 422);
            }

            // Supprimer l'image associée
            $productModel = \Src\Infrastructure\Pharmacy\Models\ProductModel::find($id);
            if ($productModel && $productModel->image_path && ($productModel->image_type ?? 'upload') === 'upload') {
                $this->imageService->deleteByPath($productModel->image_path, 'upload');
            }

            $this->productRepository->delete($id);

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting product'
            ], 500);
        }
    }

    public function updateStock(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|in:add,remove,adjust',
                'quantity' => 'required|integer|min:1',
                'batch_number' => 'nullable|string|max:100',
                'expiry_date' => 'nullable|date|after:today',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'purchase_order_id' => 'nullable|string|max:100'
            ]);

            if ($request->input('type') === 'add') {
                $dto = new UpdateStockDTO(
                    $request->user()->shop_id,
                    $id,
                    (int) $request->input('quantity'),
                    $request->input('batch_number'),
                    $request->input('expiry_date'),
                    $request->input('supplier_id'),
                    $request->input('purchase_order_id')
                );
                
                $batch = $this->updateStockUseCase->addStock($dto);
                
                return response()->json([
                    'message' => 'Stock added successfully',
                    'batch' => $batch
                ]);

            } elseif ($request->input('type') === 'remove') {
                $this->updateStockUseCase->removeStock(
                    $id,
                    (int) $request->input('quantity'),
                    $request->user()->shop_id
                );
                
                return response()->json([
                    'message' => 'Stock removed successfully'
                ]);

            } else { // adjust
                $this->updateStockUseCase->adjustStock(
                    $id,
                    (int) $request->input('quantity'),
                    $request->user()->shop_id
                );
                
                return response()->json([
                    'message' => 'Stock adjusted successfully'
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}