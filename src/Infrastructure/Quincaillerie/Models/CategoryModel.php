<?php

namespace Src\Infrastructure\Quincaillerie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle Eloquent Catégorie - Module Quincaillerie.
 * Table quincaillerie_categories. Aucune dépendance Pharmacy.
 *
 * @property string $id
 * @property int $shop_id
 * @property int|null $depot_id
 * @property string $name
 * @property string|null $description
 * @property string|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property-read self|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, self> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductModel> $products
 */
class CategoryModel extends Model
{
    use SoftDeletes;

    protected $table = 'quincaillerie_categories';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'name',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'depot_id' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(ProductModel::class, 'category_id');
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
