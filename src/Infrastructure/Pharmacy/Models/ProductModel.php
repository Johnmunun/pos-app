<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductModel extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'pharmacy_products';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'code',
        'name',
        'description',
        'type',
        'dosage',
        'price_amount',
        'price_currency',
        'stock',
        'category_id',
        'is_active',
        'requires_prescription',
        'image_path',
        'image_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_prescription' => 'boolean',
        'price_amount' => 'decimal:2',
        'stock' => 'integer',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function batches()
    {
        return $this->hasMany(BatchModel::class, 'product_id');
    }

    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('stock', '<=', $threshold);
    }

    public function scopeRequiresPrescription($query)
    {
        return $query->where('requires_prescription', true);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        $symbol = match($this->price_currency) {
            'USD' => '$',
            'EUR' => '€',
            'CDF' => 'FC',
            default => $this->price_currency
        };

        return $symbol . number_format($this->price_amount, 2);
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock === 0) {
            return 'out_of_stock';
        } elseif ($this->stock <= 10) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    // Mutators
    public function setPriceAmountAttribute($value)
    {
        $this->attributes['price_amount'] = round($value, 2);
    }
}