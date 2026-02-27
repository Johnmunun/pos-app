<?php

namespace Src\Infrastructure\Quincaillerie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle Eloquent Produit - Module Quincaillerie.
 * Table quincaillerie_products. Aucune dépendance Pharmacy.
 *
 * @property string $id
 * @property int $shop_id
 * @property int|null $depot_id
 * @property string|null $code
 * @property string $name
 * @property string|null $description
 * @property float $price_amount
 * @property string $price_currency
 * @property float $stock
 * @property string $type_unite
 * @property int $quantite_par_unite
 * @property bool $est_divisible
 * @property float $minimum_stock
 * @property string $category_id
 * @property bool $is_active
 */
class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'quincaillerie_products';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'code',
        'name',
        'description',
        'price_amount',
        'price_currency',
        'stock',
        'type_unite',
        'quantite_par_unite',
        'est_divisible',
        'minimum_stock',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'est_divisible' => 'boolean',
        'quantite_par_unite' => 'integer',
        'price_amount' => 'decimal:2',
        'stock' => 'float',
        'minimum_stock' => 'float',
        'depot_id' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_id');
    }

    public function scopeByShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
