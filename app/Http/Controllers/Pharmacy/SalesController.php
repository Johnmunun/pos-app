<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StoreSaleRequest;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    /**
     * Display sales page (POS)
     */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if ($tenantId === null) {
            return Inertia::render('Pharmacy/Sales', [
                'products' => [],
                'categories' => [],
            ]);
        }

        $products = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['category', 'batches' => function ($query) {
                $query->where('is_active', true)
                    ->where('expiration_date', '>', now())
                    ->orderBy('expiration_date', 'asc');
            }])
            ->get()
            ->map(function ($product) {
                $availableStock = $product->batches->sum('quantity');
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => (float) $product->selling_price,
                    'currency' => $product->currency,
                    'currency_symbol' => $product->currency_symbol,
                    'image_url' => $product->image_url,
                    'barcode' => $product->barcode,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category?->name,
                    'available_stock' => $availableStock,
                    'tax_rate' => (float) ($product->tax_rate ?? 0),
                ];
            });

        $categories = Category::forTenant($tenantId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        return Inertia::render('Pharmacy/Sales', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new sale
     */
    public function store(StoreSaleRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $userId = Auth::id();

        if ($tenantId === null) {
            return back()->withErrors(['error' => 'Tenant ID is required']);
        }

        try {
            DB::beginTransaction();

            // Generate sale number
            $saleNumber = Sale::generateSaleNumber($tenantId);

            // Calculate totals
            $subtotal = (float) $request->input('subtotal', 0);
            $taxAmount = (float) $request->input('tax_amount', 0);
            $discountAmount = (float) $request->input('discount_amount', 0);
            $total = (float) $request->input('total', 0);

            // Create sale
            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'seller_id' => $userId,
                'customer_id' => $request->input('customer_id'),
                'sale_number' => $saleNumber,
                'status' => 'completed',
                'payment_type' => $request->input('payment_type'),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'notes' => $request->input('notes'),
                'sold_at' => now(),
            ]);

            // Process items and update stock
            foreach ($request->input('items', []) as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $quantity = (int) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $taxRate = (float) ($itemData['tax_rate'] ?? 0);
                $itemDiscount = (float) ($itemData['discount_amount'] ?? 0);

                // Calculate item totals
                $itemSubtotal = ($unitPrice * $quantity) - $itemDiscount;
                $itemTaxAmount = $itemSubtotal * ($taxRate / 100);
                $itemTotal = $itemSubtotal + $itemTaxAmount;

                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                    'tax_amount' => $itemTaxAmount,
                    'total' => $itemTotal,
                ]);

                // Deduct stock from batches (FIFO - First In First Out)
                $remainingQuantity = $quantity;
                $batches = ProductBatch::where('product_id', $product->id)
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where('expiration_date', '>', now())
                    ->orderBy('expiration_date', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQuantity <= 0) {
                        break;
                    }

                    $deductQuantity = min($remainingQuantity, $batch->quantity);
                    $batch->quantity -= $deductQuantity;
                    $remainingQuantity -= $deductQuantity;

                    if ($batch->quantity <= 0) {
                        $batch->is_active = false;
                    }

                    $batch->save();
                }

                // If not enough stock, rollback
                if ($remainingQuantity > 0) {
                    DB::rollBack();
                    return back()->withErrors([
                        'error' => "Stock insuffisant pour le produit: {$product->name}. Stock disponible: " . ($quantity - $remainingQuantity)
                    ]);
                }
            }

            DB::commit();

            // Return sale data instead of flash message (frontend will handle toast)
            return redirect()->route('pharmacy.sales')
                ->with('sale_data', [
                    'sale_number' => $saleNumber,
                    'total' => $total,
                    'payment_type' => $request->input('payment_type'),
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de l\'enregistrement de la vente: ' . $e->getMessage()]);
        }
    }
}

