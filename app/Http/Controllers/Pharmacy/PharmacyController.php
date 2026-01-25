<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StoreProductRequest;
use App\Http\Requests\Pharmacy\UpdateProductRequest;
use App\Http\Requests\Pharmacy\StoreBatchRequest;
use App\Helpers\ImageHelper;
use App\Helpers\CurrencyHelper;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PharmacyController extends Controller
{
    /**
     * Pharmacy Dashboard
     */
    public function dashboard(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        // Sales statistics for today
        $todaySales = Sale::where('tenant_id', $tenantId)
            ->whereDate('sold_at', today())
            ->where('status', 'completed');

        $totalSalesToday = $todaySales->sum('total');
        $salesCountToday = $todaySales->count();

        // Payment type distribution
        $paymentDistribution = Sale::where('tenant_id', $tenantId)
            ->whereDate('sold_at', today())
            ->where('status', 'completed')
            ->selectRaw('payment_type, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_type')
            ->get()
            ->map(fn($item) => [
                'type' => $item->payment_type,
                'count' => (int) $item->count,
                'total' => (float) $item->total,
            ]);

        // Recent sales
        $recentSales = Sale::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->with('seller')
            ->orderBy('sold_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($sale) => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => (float) $sale->total,
                'payment_type' => $sale->payment_type,
                'sold_at' => $sale->sold_at?->format('Y-m-d H:i'),
                'seller_name' => $sale->seller?->name ?? 'N/A',
            ]);

        $stats = [
            'total_products' => Product::where('tenant_id', $tenantId)->count(),
            'low_stock' => Product::where('tenant_id', $tenantId)
                ->whereColumn('stock_alert_level', '>=', DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM product_batches WHERE product_batches.product_id = products.id AND product_batches.is_active = 1)'))
                ->count(),
            'expiring_soon' => ProductBatch::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('expiration_date', '<=', now()->addDays(30))
                ->where('expiration_date', '>', now())
                ->count(),
            'expired' => ProductBatch::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('expiration_date', '<=', now())
                ->count(),
            'sales_today_total' => (float) $totalSalesToday,
            'sales_today_count' => $salesCountToday,
            'payment_distribution' => $paymentDistribution,
            'recent_sales' => $recentSales,
        ];

        return Inertia::render('Pharmacy/Dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * List products
     */
    public function products(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $products = Product::where('tenant_id', $tenantId)
            ->with(['batches' => function($query) {
                $query->where('is_active', true);
            }])
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'currency' => $product->currency,
                    'currency_symbol' => $product->currency_symbol,
                    'image_url' => $product->image_url,
                    'stock_alert_level' => $product->stock_alert_level,
                    'total_stock' => $product->total_stock,
                    'is_low_stock' => $product->isLowStock(),
                    'has_expiring' => $product->hasExpiringBatches(),
                    'batches' => $product->batches->map(fn($batch) => [
                        'id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'manufacturing_date' => $batch->manufacturing_date?->format('Y-m-d'),
                        'expiration_date' => $batch->expiration_date?->format('Y-m-d'),
                        'quantity' => $batch->quantity,
                        'purchase_price' => $batch->purchase_price,
                        'is_expired' => $batch->isExpired(),
                        'is_expiring_soon' => $batch->isExpiringSoon(),
                    ]),
                ];
            });

        return Inertia::render('Pharmacy/Products', [
            'products' => $products,
            'currencies' => CurrencyHelper::getCurrencies($tenantId),
        ]);
    }

    /**
     * Show create product form
     */
    public function createProduct(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = \App\Models\Category::where('tenant_id', $tenantId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        return Inertia::render('Pharmacy/ProductForm', [
            'currencies' => CurrencyHelper::getCurrencies($tenantId),
            'categories' => $categories,
        ]);
    }

    /**
     * Store product
     */
    public function storeProduct(StoreProductRequest $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $data = $request->validated();

        // Verify category belongs to tenant
        if (isset($data['category_id'])) {
            $category = \App\Models\Category::find($data['category_id']);
            if (!$category || $category->tenant_id !== $tenantId) {
                return redirect()->back()->withErrors(['category_id' => 'Catégorie invalide.'])->withInput();
            }
        }

        // Handle image
        $image = null;
        $imageType = $data['image_type'] ?? 'url';
        
        if ($imageType === 'upload' && $request->hasFile('image_file')) {
            $image = ImageHelper::storeImage($request->file('image_file'), null, 'upload');
        } elseif ($imageType === 'url' && !empty($data['image'])) {
            $image = $data['image'];
        }

        $product = Product::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
            'selling_price' => $data['selling_price'],
            'purchase_price' => $data['purchase_price'] ?? 0,
            'currency' => $data['currency'],
            'manufacturer' => $data['manufacturer'] ?? null,
            'prescription_required' => $data['prescription_required'] ?? false,
            'stock_alert_level' => $data['stock_alert_level'] ?? 0,
            'barcode' => $data['barcode'] ?? null,
            'image' => $image,
            'image_type' => $imageType,
            'is_active' => true,
        ]);

        return redirect()->route('pharmacy.products')->with('success', 'Produit créé avec succès.');
    }

    /**
     * Show edit product form
     */
    public function editProduct(Product $product): Response
    {
        $tenantId = Auth::user()->tenant_id;

        if ($product->tenant_id !== $tenantId) {
            abort(403);
        }

        $categories = \App\Models\Category::where('tenant_id', $tenantId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        return Inertia::render('Pharmacy/ProductForm', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'currency' => $product->currency,
                'manufacturer' => $product->manufacturer,
                'prescription_required' => $product->prescription_required,
                'stock_alert_level' => $product->stock_alert_level,
                'barcode' => $product->barcode,
                'image' => $product->image,
                'image_type' => $product->image_type,
                'image_url' => $product->image_url,
            ],
            'currencies' => CurrencyHelper::getCurrencies($tenantId),
            'categories' => $categories,
        ]);
    }

    /**
     * Update product
     */
    public function updateProduct(UpdateProductRequest $request, Product $product)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($product->tenant_id !== $tenantId) {
            abort(403);
        }

        $data = $request->validated();

        // Verify category belongs to tenant
        if (isset($data['category_id'])) {
            $category = \App\Models\Category::find($data['category_id']);
            if (!$category || $category->tenant_id !== $tenantId) {
                return redirect()->back()->withErrors(['category_id' => 'Catégorie invalide.'])->withInput();
            }
        }

        // Handle image
        $image = $product->image;
        $imageType = $data['image_type'] ?? $product->image_type ?? 'url';
        
        if ($imageType === 'upload' && $request->hasFile('image_file')) {
            // Delete old image if it was an upload
            if ($product->image_type === 'upload') {
                ImageHelper::deleteImage($product->image, 'upload');
            }
            $image = ImageHelper::storeImage($request->file('image_file'), null, 'upload');
        } elseif ($imageType === 'url' && !empty($data['image'])) {
            // Delete old image if it was an upload
            if ($product->image_type === 'upload') {
                ImageHelper::deleteImage($product->image, 'upload');
            }
            $image = $data['image'];
        }

        $product->update([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
            'selling_price' => $data['selling_price'],
            'purchase_price' => $data['purchase_price'] ?? 0,
            'currency' => $data['currency'],
            'manufacturer' => $data['manufacturer'] ?? null,
            'prescription_required' => $data['prescription_required'] ?? false,
            'stock_alert_level' => $data['stock_alert_level'] ?? 0,
            'barcode' => $data['barcode'] ?? null,
            'image' => $image,
            'image_type' => $imageType,
        ]);

        return redirect()->route('pharmacy.products')->with('success', 'Produit mis à jour avec succès.');
    }

    /**
     * Delete product
     */
    public function destroyProduct(Product $product)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($product->tenant_id !== $tenantId) {
            abort(403);
        }

        // Delete image if uploaded
        if ($product->image_type === 'upload') {
            ImageHelper::deleteImage($product->image, 'upload');
        }

        $product->delete();

        return redirect()->route('pharmacy.products')->with('success', 'Produit supprimé avec succès.');
    }

    /**
     * Store batch
     */
    public function storeBatch(StoreBatchRequest $request, Product $product)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($product->tenant_id !== $tenantId) {
            abort(403);
        }

        $data = $request->validated();

        ProductBatch::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'batch_number' => $data['batch_number'],
            'manufacturing_date' => $data['manufacturing_date'] ?? null,
            'expiration_date' => $data['expiration_date'],
            'quantity' => $data['quantity'],
            'purchase_price' => $data['purchase_price'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Lot ajouté avec succès.');
    }

    /**
     * Update batch
     */
    public function updateBatch(StoreBatchRequest $request, Product $product, ProductBatch $batch)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($product->tenant_id !== $tenantId || $batch->product_id !== $product->id) {
            abort(403);
        }

        $data = $request->validated();

        $batch->update($data);

        return redirect()->back()->with('success', 'Lot mis à jour avec succès.');
    }

    /**
     * Delete batch
     */
    public function destroyBatch(Product $product, ProductBatch $batch)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($product->tenant_id !== $tenantId || $batch->product_id !== $product->id) {
            abort(403);
        }

        $batch->delete();

        return redirect()->back()->with('success', 'Lot supprimé avec succès.');
    }

    /**
     * Stock management
     */
    public function stock(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $lowStock = Product::where('tenant_id', $tenantId)
            ->with('batches')
            ->get()
            ->filter(fn($product) => $product->isLowStock())
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'total_stock' => $product->total_stock,
                'stock_alert_level' => $product->stock_alert_level,
            ])
            ->values();

        $expiringSoon = ProductBatch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('expiration_date', '<=', now()->addDays(30))
            ->where('expiration_date', '>', now())
            ->with('product')
            ->get()
            ->map(fn($batch) => [
                'id' => $batch->id,
                'product_name' => $batch->product->name,
                'batch_number' => $batch->batch_number,
                'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                'quantity' => $batch->quantity,
                'days_until_expiry' => now()->diffInDays($batch->expiration_date),
            ]);

        return Inertia::render('Pharmacy/Stock', [
            'lowStock' => $lowStock,
            'expiringSoon' => $expiringSoon,
        ]);
    }

    /**
     * Sales (POS)
     */
    public function sales(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $products = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'selling_price' => $product->selling_price,
                'currency' => $product->currency,
                'currency_symbol' => $product->currency_symbol,
                'image_url' => $product->image_url,
                'barcode' => $product->barcode,
            ]);

        return Inertia::render('Pharmacy/Sales', [
            'products' => $products,
        ]);
    }

    /**
     * Expiry management
     */
    public function expiry(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $expiringSoon = ProductBatch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('expiration_date', '<=', now()->addDays(30))
            ->where('expiration_date', '>', now())
            ->with('product')
            ->get()
            ->map(fn($batch) => [
                'id' => $batch->id,
                'product_name' => $batch->product->name,
                'batch_number' => $batch->batch_number,
                'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                'quantity' => $batch->quantity,
                'days_until_expiry' => now()->diffInDays($batch->expiration_date),
            ]);

        $expired = ProductBatch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('expiration_date', '<=', now())
            ->with('product')
            ->get()
            ->map(fn($batch) => [
                'id' => $batch->id,
                'product_name' => $batch->product->name,
                'batch_number' => $batch->batch_number,
                'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                'quantity' => $batch->quantity,
                'days_expired' => now()->diffInDays($batch->expiration_date),
            ]);

        return Inertia::render('Pharmacy/Expiry', [
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
        ]);
    }

    /**
     * Reports
     */
    public function reports(): Response
    {
        return Inertia::render('Pharmacy/Reports');
    }
}
