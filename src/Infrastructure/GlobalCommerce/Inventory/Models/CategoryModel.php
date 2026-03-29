<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Eloquent Catégorie - Module GlobalCommerce.
 * Table gc_categories.
 *
 * @property string $id
 * @property int $shop_id
 * @property string $name
 * @property string|null $description
 * @property string|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, self> $children
 */
class CategoryModel extends Model
{
    protected $table = 'gc_categories';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'category_code',
        'name',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
