<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Src\Application\Pharmacy\UseCases\Product\CreateProductUseCase;
use Src\Application\Pharmacy\UseCases\Product\UpdateProductUseCase;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Application\Pharmacy\UseCases\Product\GenerateProductCodeUseCase;
use Src\Application\Pharmacy\UseCases\Product\ImportProductsUseCase;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\CreateProductDTO;
use Src\Application\Pharmacy\DTO\UpdateProductDTO;
use Src\Application\Pharmacy\DTO\UpdateStockDTO;
use Src\Infrastructure\Pharmacy\Services\ProductImageService;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;
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
        private GenerateProductCodeUseCase $generateProductCodeUseCase,
        private ImportProductsUseCase $importProductsUseCase,
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private ProductImageService $imageService,
        private GetStoreSettingsUseCase $getStoreSettingsUseCase,
        private StoreLogoService $storeLogoService
    ) {}

    /**
     * Génération automatique d'un code produit valide et unique.
     * Utilisé par le bouton "Générer le code produit" côté frontend.
     */
    public function generateCode(Request $request): JsonResponse
    {
        $name = (string) $request->input('name', '');

        $productCode = $this->generateProductCodeUseCase->execute($name);

        return response()->json([
            'code' => (string) $productCode,
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        
        // ROOT users can access all shops, others need shop_id
        // For now, use tenant_id as shop_id if shop_id doesn't exist
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        // Ensure UserModel has isRoot() method
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
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
        $query = \Src\Infrastructure\Pharmacy\Models\ProductModel::with('category')->orderBy('name');
        if (!($isRoot && !$shopId)) {
            $query->where('shop_id', $shopId);
        }

        // Filtres
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
                'product_code' => $model->code ?? $model->product_code ?? '',
                'description' => $model->description ?? '',
                'category_id' => $model->category_id,
                'price_amount' => (float) ($model->price_amount ?? 0),
                'wholesale_price_amount' => $model->wholesale_price_amount !== null ? (float) $model->wholesale_price_amount : null,
                'wholesale_min_quantity' => $model->wholesale_min_quantity !== null ? (int) $model->wholesale_min_quantity : null,
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
            $categoryModels = \Src\Infrastructure\Pharmacy\Models\CategoryModel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $categoryModels = \Src\Infrastructure\Pharmacy\Models\CategoryModel::query()
                ->where('shop_id', $shopId)
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
            'filters' => $request->only(['search', 'category_id', 'status']),
            'canImport' => $isRoot || $user->can('pharmacy.product.import'),
        ]);
    }

    /**
     * Export PDF de la liste des produits pour la boutique courante.
     */
    public function exportPdf(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        // Déterminer le shop courant (même logique que index)
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        // Récupérer les paramètres de boutique
        $settings = null;
        if ($shopId) {
            $settings = $this->getStoreSettingsUseCase->execute((string) $shopId);
        }

        $header = [
            'company_name' => $settings ? $settings->getCompanyIdentity()->getName() : ($user->shop->name ?? 'Boutique'),
            'id_nat' => $settings ? $settings->getCompanyIdentity()->getIdNat() : null,
            'rccm' => $settings ? $settings->getCompanyIdentity()->getRccm() : null,
            'tax_number' => $settings ? $settings->getCompanyIdentity()->getTaxNumber() : null,
            'street' => $settings ? $settings->getAddress()->getStreet() : null,
            'city' => $settings ? $settings->getAddress()->getCity() : null,
            'postal_code' => $settings ? $settings->getAddress()->getPostalCode() : null,
            'country' => $settings ? $settings->getAddress()->getCountry() : null,
            'phone' => $settings ? $settings->getPhone() : null,
            'email' => $settings ? $settings->getEmail() : null,
            'logo_url' => $settings && $settings->getLogoPath()
                ? $this->storeLogoService->getUrl($settings->getLogoPath())
                : null,
            'exported_at' => now(),
        ];

        // Récupérer les produits pour ce shop
        $query = \Src\Infrastructure\Pharmacy\Models\ProductModel::with('category')
            ->orderBy('name');

        if (!($isRoot && !$shopId)) {
            $query->where('shop_id', $shopId);
        }

        $models = $query->get();

        $products = $models->map(function ($model) {
            return [
                'name' => $model->name,
                'code' => $model->code ?? '',
                'category' => $model->category ? $model->category->name : null,
                'unit' => $model->unit ?? '',
                'price_amount' => (float) ($model->price_amount ?? 0),
                'price_currency' => $model->price_currency ?? 'USD',
                'cost_amount' => isset($model->cost_amount) ? (float) $model->cost_amount : null,
                'stock' => (int) ($model->stock ?? 0),
                'is_active' => (bool) ($model->is_active ?? true),
            ];
        })->toArray();

        $data = [
            'header' => $header,
            'products' => $products,
        ];

        $pdf = Pdf::loadView('pharmacy.products.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'produits_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export Excel (XLSX) de la liste des produits pour la boutique courante.
     * Fichier structuré, colonnes alignées, design professionnel ERP.
     */
    public function exportExcel(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        $settings = null;
        if ($shopId) {
            $settings = $this->getStoreSettingsUseCase->execute((string) $shopId);
        }

        $header = [
            'company_name' => $settings ? $settings->getCompanyIdentity()->getName() : ($user->shop->name ?? 'Boutique'),
            'id_nat' => $settings ? $settings->getCompanyIdentity()->getIdNat() : null,
            'rccm' => $settings ? $settings->getCompanyIdentity()->getRccm() : null,
            'tax_number' => $settings ? $settings->getCompanyIdentity()->getTaxNumber() : null,
            'street' => $settings ? $settings->getAddress()->getStreet() : null,
            'city' => $settings ? $settings->getAddress()->getCity() : null,
            'postal_code' => $settings ? $settings->getAddress()->getPostalCode() : null,
            'country' => $settings ? $settings->getAddress()->getCountry() : null,
            'phone' => $settings ? $settings->getPhone() : null,
            'email' => $settings ? $settings->getEmail() : null,
            'exported_at' => now(),
        ];

        $query = \Src\Infrastructure\Pharmacy\Models\ProductModel::with('category')
            ->orderBy('name');

        if (!($isRoot && !$shopId)) {
            $query->where('shop_id', $shopId);
        }

        $models = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produits');

        $row = 1;

        // Lignes 1–3 : informations boutique
        $sheet->setCellValue('A' . $row, 'Boutique');
        $sheet->setCellValue('B' . $row, $header['company_name'] ?? '');
        $row++;

        $addressParts = array_filter([
            $header['street'] ?? null,
            $header['city'] ?? null,
            $header['postal_code'] ?? null,
            $header['country'] ?? null,
        ]);
        $sheet->setCellValue('A' . $row, 'Adresse');
        $sheet->setCellValue('B' . $row, implode(', ', $addressParts));
        $row++;

        $sheet->setCellValue('A' . $row, 'Contact');
        $contact = trim(($header['phone'] ? 'Tél: ' . $header['phone'] . ' ' : '') . ($header['email'] ? 'Email: ' . $header['email'] : ''));
        $sheet->setCellValue('B' . $row, $contact);
        $row++;

        if (!empty($header['id_nat']) || !empty($header['rccm']) || !empty($header['tax_number'])) {
            $legal = array_filter([
                $header['id_nat'] ? 'ID NAT: ' . $header['id_nat'] : null,
                $header['rccm'] ? 'RCCM: ' . $header['rccm'] : null,
                $header['tax_number'] ? 'N° Tva: ' . $header['tax_number'] : null,
            ]);
            $sheet->setCellValue('A' . $row, 'Identification');
            $sheet->setCellValue('B' . $row, implode(' · ', $legal));
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Date d\'export');
        $sheet->setCellValue('B' . $row, $header['exported_at']->format('d/m/Y H:i'));
        $row++;

        $row++; // Ligne vide

        // En-têtes colonnes produits (ligne 7+)
        $headerRow = $row;
        $headers = ['Nom', 'Code', 'Catégorie', 'Unité', 'Prix de vente', 'Prix de revient', 'Stock', 'Statut'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $label);
        }
        $row++;

        // Style en-têtes : fond gris, gras
        $headerRange = 'A' . $headerRow . ':H' . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Données produits
        foreach ($models as $model) {
            $status = ($model->is_active ?? true) ? 'Actif' : 'Inactif';
            $price = ($model->price_currency ?? 'USD') . ' ' . number_format((float) ($model->price_amount ?? 0), 2);
            $cost = isset($model->cost_amount) && $model->cost_amount > 0
                ? ($model->price_currency ?? 'USD') . ' ' . number_format((float) $model->cost_amount, 2)
                : '';

            $sheet->setCellValue('A' . $row, $model->name);
            $sheet->setCellValue('B' . $row, $model->code ?? '');
            $sheet->setCellValue('C' . $row, $model->category ? $model->category->name : '');
            $sheet->setCellValue('D' . $row, $model->unit ?? '');
            $sheet->setCellValue('E' . $row, $price);
            $sheet->setCellValue('F' . $row, $cost);
            $sheet->setCellValue('G' . $row, (int) ($model->stock ?? 0));
            $sheet->setCellValue('H' . $row, $status);
            $row++;
        }

        // Alignement : texte à gauche, prix et stock à droite
        $dataStartRow = $headerRow + 1;
        $dataEndRow = $row - 1;
        if ($dataEndRow >= $dataStartRow) {
            $sheet->getStyle('A' . $dataStartRow . ':D' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E' . $dataStartRow . ':G' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $dataStartRow . ':H' . $dataEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Bordures légères sur la zone données
        $dataRange = 'A' . $headerRow . ':H' . $dataEndRow;
        if ($dataEndRow >= $headerRow) {
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
        }

        // Auto-size des colonnes
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'produits_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import de produits depuis fichier XLSX ou CSV.
     * Permission requise : pharmacy.product.import
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
        if (!$shopId) {
            return response()->json(['message' => 'Shop ID non trouvé.'], 403);
        }

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();

            $result = $this->importProductsUseCase->execute($shopId, $path);

            $errorsFormatted = [];
            foreach ($result['errors'] as $line => $msg) {
                $errorsFormatted[] = "Ligne {$line}: {$msg}";
            }

            return response()->json([
                'message' => 'Import terminé.',
                'success' => $result['success'],
                'failed' => $result['failed'],
                'total' => $result['total'],
                'errors' => $errorsFormatted,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Product import error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Erreur lors de l\'import : ' . $e->getMessage(),
            ], 422);
        }
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
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
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
                'wholesale_price' => 'nullable|numeric|min:0',
                'wholesale_min_quantity' => 'nullable|integer|min:0',
            ]);

            $dto = new CreateProductDTO(
                $shopId,
                $request->input('product_code'),
                $request->input('name'),
                $request->input('category_id'),
                (float) $request->input('price'),
                $request->input('currency'),
                (int) $request->input('minimum_stock'),
                $request->input('unit'),
                $request->input('description') ?: null,
                $request->filled('cost') ? (float) $request->input('cost') : null,
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
                    Log::warning('Failed to upload product image', [
                        'product_id' => $product->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($request->filled('image_url')) {
                $imagePath = $request->input('image_url');
                $imageType = 'url';
            }

            // Mettre à jour le produit avec les champs infra (unité, stock min, prix de revient, fabricant, image)
            $productUpdateData = [
                'unit' => $request->input('unit'),
                'minimum_stock' => (int) $request->input('minimum_stock'),
            ];

            if ($request->filled('cost')) {
                $productUpdateData['cost_amount'] = (float) $request->input('cost');
            }

            if ($request->filled('manufacturer')) {
                $productUpdateData['manufacturer'] = $request->input('manufacturer');
            }

            if ($imagePath) {
                $productUpdateData['image_path'] = $imagePath;
                $productUpdateData['image_type'] = $imageType;
            }

            if ($request->filled('wholesale_price')) {
                $productUpdateData['wholesale_price_amount'] = (float) $request->input('wholesale_price');
            }
            if ($request->has('wholesale_min_quantity') && $request->input('wholesale_min_quantity') !== null && $request->input('wholesale_min_quantity') !== '') {
                $productUpdateData['wholesale_min_quantity'] = (int) $request->input('wholesale_min_quantity');
            }

            \Src\Infrastructure\Pharmacy\Models\ProductModel::where('id', $product->getId())
                ->update($productUpdateData);

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

    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel?->isRoot() ?? false;
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            abort(404);
        }

        // Vérification d'isolation par pharmacie: seuls ROOT ou les utilisateurs de la même pharmacie peuvent voir
        if (!$isRoot && $product->getShopId() !== $shopId) {
            abort(404, 'Produit non trouvé');
        }

        // Get related batches
        $batches = []; // $this->batchRepository->findByProduct($id);
        
        return Inertia::render('Pharmacy/Products/Show', [
            'product' => $product,
            'batches' => $batches
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel?->isRoot() ?? false;
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            abort(404);
        }

        // Vérification d'isolation par pharmacie: seuls ROOT ou les utilisateurs de la même pharmacie peuvent modifier
        if (!$isRoot && $product->getShopId() !== $shopId) {
            abort(404, 'Produit non trouvé');
        }

        $categories = $this->categoryRepository->findByShop(
            $shopId, 
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
                'remove_image' => 'nullable|boolean',
                'wholesale_price' => 'nullable|numeric|min:0',
                'wholesale_min_quantity' => 'nullable|integer|min:0',
            ]);

            // Déterminer le shop courant (même logique robuste que pour index/show)
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);

            // Si pas de shop_id (cas ROOT sans shop), on se rabat sur le shop du produit existant
            if (!$shopId) {
                $existingProductModel = \Src\Infrastructure\Pharmacy\Models\ProductModel::find($id);
                if ($existingProductModel) {
                    $shopId = (string) $existingProductModel->shop_id;
                }
            }

            // Si toujours pas de shopId, on ne peut pas continuer proprement
            if (!$shopId) {
                return response()->json([
                    'message' => 'Shop ID not found. Please contact administrator.'
                ], 403);
            }

            $dto = new UpdateProductDTO(
                $shopId,
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

            // Gérer l'upload/suppression d'image + champs infra (unité, stock min, prix de revient, fabricant)
            $productModel = \Src\Infrastructure\Pharmacy\Models\ProductModel::find($id);
            if ($productModel) {
                $productUpdateData = [];

                if ($request->input('unit') !== null) {
                    $productUpdateData['unit'] = $request->input('unit');
                }

                if ($request->input('minimum_stock') !== null) {
                    $productUpdateData['minimum_stock'] = (int) $request->input('minimum_stock');
                }

                if ($request->input('cost') !== null) {
                    $productUpdateData['cost_amount'] = (float) $request->input('cost');
                }

                if ($request->input('manufacturer') !== null) {
                    $productUpdateData['manufacturer'] = $request->input('manufacturer');
                }

                if ($request->filled('wholesale_price')) {
                    $productUpdateData['wholesale_price_amount'] = (float) $request->input('wholesale_price');
                }
                if ($request->has('wholesale_min_quantity') && $request->input('wholesale_min_quantity') !== null && $request->input('wholesale_min_quantity') !== '') {
                    $productUpdateData['wholesale_min_quantity'] = (int) $request->input('wholesale_min_quantity');
                }

                // Supprimer l'image existante si demandé
                if ($request->boolean('remove_image')) {
                    if ($productModel->image_path) {
                        $this->imageService->deleteByPath($productModel->image_path, $productModel->image_type ?? 'upload');
                    }
                    $productUpdateData['image_path'] = null;
                    $productUpdateData['image_type'] = 'upload';
                }
                // Upload nouvelle image
                elseif ($request->hasFile('image')) {
                    try {
                        // Supprimer l'ancienne image si elle existe
                        if ($productModel->image_path && ($productModel->image_type ?? 'upload') === 'upload') {
                            $this->imageService->deleteByPath($productModel->image_path, 'upload');
                        }
                        
                        $productImage = $this->imageService->upload($request->file('image'), $id);
                        $productUpdateData['image_path'] = $productImage->getPath();
                        $productUpdateData['image_type'] = $productImage->getType();
                    } catch (\Exception $e) {
                        Log::warning('Failed to upload product image', [
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
                    
                    $productUpdateData['image_path'] = $request->input('image_url');
                    $productUpdateData['image_type'] = 'url';
                }

                if (!empty($productUpdateData)) {
                    $productModel->update($productUpdateData);
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

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user === null) {
                abort(403, 'User not authenticated.');
            }
            /** @var UserModel|null $userModel */
            $userModel = UserModel::query()->find($user->id);
            $isRoot = $userModel?->isRoot() ?? false;
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            
            $product = $this->productRepository->findById($id);
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found'
                ], 404);
            }

            // Vérification d'isolation par pharmacie: seuls ROOT ou les utilisateurs de la même pharmacie peuvent supprimer
            if (!$isRoot && $product->getShopId() !== $shopId) {
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

    public function updateStock(Request $request, string $id): JsonResponse|\Illuminate\Http\RedirectResponse
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

            $authUser = $request->user();
            if ($authUser === null) {
                abort(403, 'User not authenticated.');
            }
            /** @var UserModel $authUser */
            $shopId = $authUser->shop_id ?? ($authUser->tenant_id ? (string) $authUser->tenant_id : null);

            if (!$shopId) {
                return response()->json([
                    'message' => 'Shop ID not found. Please contact administrator.'
                ], 403);
            }

            // Vérifier que le produit appartient à cette pharmacie
            $product = $this->productRepository->findById($id);
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found'
                ], 404);
            }

            /** @var UserModel|null $userModel */
            $userModel = UserModel::query()->find($authUser->id);
            $isRoot = $userModel?->isRoot() ?? false;
            
            if (!$isRoot && $product->getShopId() !== $shopId) {
                return response()->json([
                    'message' => 'Product not found'
                ], 404);
            }

            if ($request->input('type') === 'add') {
                $dto = new UpdateStockDTO(
                    (string) $shopId,
                    $id,
                    (int) $request->input('quantity'),
                    $request->input('batch_number'),
                    $request->input('expiry_date'),
                    $request->input('supplier_id'),
                    $request->input('purchase_order_id'),
                    (int) $authUser->id
                );
                
                $batch = $this->updateStockUseCase->addStock($dto);

                if ($request->header('X-Inertia')) {
                    return redirect()->back()->with('success', 'Stock ajouté avec succès');
                }
                return response()->json([
                    'message' => 'Stock added successfully',
                    'batch' => $batch,
                ]);

            } elseif ($request->input('type') === 'remove') {
                $this->updateStockUseCase->removeStock(
                    $id,
                    (int) $request->input('quantity'),
                    (string) $shopId,
                    (int) $authUser->id
                );

                if ($request->header('X-Inertia')) {
                    return redirect()->back()->with('success', 'Stock retiré avec succès');
                }
                return response()->json([
                    'message' => 'Stock removed successfully',
                ]);

            } else { // adjust
                $this->updateStockUseCase->adjustStock(
                    $id,
                    (int) $request->input('quantity'),
                    (string) $shopId,
                    (int) $authUser->id
                );

                if ($request->header('X-Inertia')) {
                    return redirect()->back()->with('success', 'Stock ajusté avec succès');
                }
                return response()->json([
                    'message' => 'Stock adjusted successfully',
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}