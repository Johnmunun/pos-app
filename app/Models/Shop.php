<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'currency',
        'default_tax_rate',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_tax_rate' => 'decimal:2',
        ];
    }

    /**
     * Get the tenant that owns the shop.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the products for the shop.
     */
    public function products()
    {
        return $this->hasMany(\Src\Infrastructure\Pharmacy\Models\ProductModel::class, 'shop_id');
    }

    /**
     * Get the categories for the shop.
     */
    public function categories()
    {
        return $this->hasMany(\Src\Infrastructure\Pharmacy\Models\CategoryModel::class, 'shop_id');
    }

    /**
     * Get the batches for the shop.
     */
    public function batches()
    {
        return $this->hasMany(\Src\Infrastructure\Pharmacy\Models\BatchModel::class, 'shop_id');
    }

    /**
     * Get the sales for the shop.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'shop_id');
    }

    /**
     * Scope a query to only include active shops.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
