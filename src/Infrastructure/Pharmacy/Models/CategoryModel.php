<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryModel extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'pharmacy_categories';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
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

    // Relations
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildrenOf($query, string $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }

    // Methods
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}