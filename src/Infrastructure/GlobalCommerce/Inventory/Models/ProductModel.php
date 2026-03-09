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
 * @property array|null $extra_images
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
 * @property bool $is_published_ecommerce
 * @property string|null $product_type physical|digital
 * @property string|null $download_url
 * @property string|null $download_path
 * @property bool $requires_shipping
 * @property string|null $unit
 * @property float|null $weight
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property float|null $tax_rate
 * @property string|null $tax_type
 * @property string|null $status
 * @property string|null $couleur
 * @property string|null $taille
 * @property string|null $type_produit physique|numerique
 * @property string|null $mode_paiement paiement_immediat|paiement_livraison
 * @property string|null $lien_telechargement
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
        'download_url',
        'download_path',
        'requires_shipping',
        'unit',
        'weight',
        'length',
        'width',
        'height',
        'tax_rate',
        'tax_type',
        'status',
        'couleur',
        'taille',
        'type_produit',
        'mode_paiement',
        'lien_telechargement',
        'image_path',
        'image_type',
        'extra_images',
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
        'is_published_ecommerce',
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
        'is_published_ecommerce' => 'boolean',
        'requires_shipping' => 'boolean',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'tax_rate' => 'float',
        'extra_images' => 'array',
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
