<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int|null $tenant_id
 * @property int|null $depot_id
 * @property string $name
 * @property string|null $code
 * @property string|null $type
 * @property string|null $address
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $phone
     * @property string|null $email
     * @property string|null $ecommerce_subdomain
     * @property bool $ecommerce_is_online
     * @property string|null $currency
 * @property float|null $default_tax_rate
 * @property array<mixed>|null $ecommerce_storefront_config
 * @property bool $is_active
 */
class Shop extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'depot_id',
        'name',
        'code',
        'type',
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'ecommerce_subdomain',
        'ecommerce_is_online',
        'currency',
        'default_tax_rate',
        'ecommerce_storefront_config',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ecommerce_is_online' => 'boolean',
            'default_tax_rate' => 'decimal:2',
            'ecommerce_storefront_config' => 'array',
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
     * Get the depot associated with this shop.
     */
    public function depot()
    {
        return $this->belongsTo(Depot::class);
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
