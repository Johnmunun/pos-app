<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Eloquent Produit - Module GlobalCommerce.
 * Table gc_products.
 *
 * @property string $id
 * @property int $shop_id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property string|null $description
 * @property string $category_id
 * @property string|null $image_path
 * @property string|null $image_type
 * @property float|null $wholesale_price_amount
 * @property float|null $min_sale_price_amount
 * @property float|null $min_wholesale_price_amount
 * @property float|null $discount_percent
 * @property bool $price_non_negotiable
 * @property float $purchase_price_amount
 * @property string $purchase_price_currency
 * @property float $sale_price_amount
 * @property string $sale_price_currency
 * @property float $stock
 * @property float $minimum_stock
 * @property bool $is_weighted
 * @property bool $has_expiration
 * @property bool $is_active
 * @property string|null $product_type
 * @property string|null $unit
 * @property float|null $weight
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property float|null $tax_rate
 * @property string|null $tax_type
 * @property string|null $status
 */
class ProductModel extends Model
{
    protected $table = 'gc_products';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'sku',
        'barcode',
        'name',
        'description',
        'product_type',
        'unit',
        'weight',
        'length',
        'width',
        'height',
        'tax_rate',
        'tax_type',
        'status',
        'image_path',
        'image_type',
        'category_id',
        'purchase_price_amount',
        'purchase_price_currency',
        'sale_price_amount',
        'sale_price_currency',
        'wholesale_price_amount',
        'min_sale_price_amount',
        'min_wholesale_price_amount',
        'discount_percent',
        'price_non_negotiable',
        'stock',
        'minimum_stock',
        'is_weighted',
        'has_expiration',
        'is_active',
    ];

    protected $casts = [
        'purchase_price_amount' => 'float',
        'sale_price_amount' => 'float',
        'wholesale_price_amount' => 'float',
        'min_sale_price_amount' => 'float',
        'min_wholesale_price_amount' => 'float',
        'discount_percent' => 'float',
        'price_non_negotiable' => 'boolean',
        'stock' => 'float',
        'minimum_stock' => 'float',
        'is_weighted' => 'boolean',
        'has_expiration' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'tax_rate' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
