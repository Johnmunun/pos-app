<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'shop_id',
        'category_id',
        'sku',
        'name',
        'description',
        'purchase_price',
        'selling_price',
        'category_id',
        'tax_rate',
        'unit',
        'image',
        'images',
        'barcode',
        'weight',
        'length',
        'width',
        'height',
        'min_stock_level',
        'is_active',
        'is_tracked',
        // Pharmacy fields
        'manufacturer',
        'prescription_required',
        'stock_alert_level',
        // Currency & Image
        'currency',
        'image_type', // 'upload' or 'url'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_active' => 'boolean',
            'is_tracked' => 'boolean',
            'prescription_required' => 'boolean',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    /**
     * Get the image URL (from upload or external URL)
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return asset('images/default-product.png');
        }

        if ($this->image_type === 'url') {
            return $this->image;
        }

        return asset('storage/products/' . $this->image);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbolAttribute(): string
    {
        if (!$this->currency) {
            return 'USD';
        }

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'CDF' => 'FC',
            'XAF' => 'FCFA',
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Get the tenant that owns the product.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the batches for the product.
     */
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Get total stock from batches
     */
    public function getTotalStockAttribute(): int
    {
        return $this->batches()
            ->where('is_active', true)
            ->sum('quantity');
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(): bool
    {
        return $this->total_stock <= $this->stock_alert_level;
    }

    /**
     * Check if product has expiring batches
     */
    public function hasExpiringBatches(int $days = 30): bool
    {
        return $this->batches()
            ->where('is_active', true)
            ->where('expiration_date', '<=', now()->addDays($days))
            ->where('expiration_date', '>', now())
            ->exists();
    }
}