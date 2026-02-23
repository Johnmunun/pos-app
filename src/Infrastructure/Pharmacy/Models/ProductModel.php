<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property int $shop_id
 * @property string|null $code
 * @property string $name
 * @property string|null $description
 * @property string|null $type
 * @property string|null $dosage
 * @property float $price_amount
 * @property float|null $wholesale_price_amount
 * @property int|null $wholesale_min_quantity
 * @property string $price_currency
 * @property int $stock
 * @property string|null $unit
 * @property int|null $minimum_stock
 * @property float|null $cost_amount
 * @property string|null $manufacturer
 * @property string|null $category_id
 * @property bool $is_active
 * @property bool $requires_prescription
 * @property string|null $image_path
 * @property string|null $image_type
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @method static static|null find(string $id)
 * @method static static create(array<string, mixed> $attributes)
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string $column, mixed $operator = null, mixed $value = null)
 */
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
        'wholesale_price_amount',
        'wholesale_min_quantity',
        'price_currency',
        'stock',
        'unit',
        'minimum_stock',
        'cost_amount',
        'manufacturer',
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
        'wholesale_price_amount' => 'decimal:2',
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