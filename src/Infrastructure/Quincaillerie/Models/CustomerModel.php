<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Shop;

/**
 * Model: CustomerModel
 *
 * Eloquent Model pour la table des clients Quincaillerie.
 *
 * @property string $id
 * @property int $shop_id
 * @property int|null $depot_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string $customer_type
 * @property string|null $tax_number
 * @property float|null $credit_limit
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|static byShop(int $shopId)
 * @method static \Illuminate\Database\Eloquent\Builder|static active()
 * @method static \Illuminate\Database\Eloquent\Builder|static inactive()
 * @method static \Illuminate\Database\Eloquent\Builder|static search(?string $search)
 * @method static static|null find(string $id)
 * @method static static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static where($column, $operator = null, $value = null)
 */
class CustomerModel extends Model
{
    protected $table = 'quincaillerie_customers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'name',
        'phone',
        'email',
        'address',
        'customer_type',
        'tax_number',
        'credit_limit',
        'status',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'depot_id' => 'integer',
        'credit_limit' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: Appartient à une boutique.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Depot::class, 'depot_id');
    }

    /**
     * Scope: Filtrer par boutique.
     */
    public function scopeByShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filtrer les clients actifs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Filtrer les clients inactifs.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope: Rechercher par nom, téléphone ou email.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Vérifie si le client est actif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifie si le client est une entreprise.
     */
    public function isCompany(): bool
    {
        return $this->customer_type === 'company';
    }
}
