<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Shop;

/**
 * Model: SupplierModel
 *
 * Eloquent Model pour la table des fournisseurs.
 *
 * @property string $id
 * @property int $shop_id
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read int $total_orders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseOrderModel> $purchaseOrders
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|static byShop(int $shopId)
 * @method static \Illuminate\Database\Eloquent\Builder|static active()
 * @method static \Illuminate\Database\Eloquent\Builder|static inactive()
 * @method static \Illuminate\Database\Eloquent\Builder|static search(?string $search)
 * @method static static|null find(string $id)
 * @method static static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static where($column, $operator = null, $value = null)
 */
class SupplierModel extends Model
{
    protected $table = 'pharmacy_suppliers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'status',
    ];

    protected $casts = [
        'shop_id' => 'integer',
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

    /**
     * Relation: A plusieurs bons de commande.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrderModel::class, 'supplier_id');
    }

    /**
     * Scope: Filtrer par boutique.
     */
    public function scopeByShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filtrer les fournisseurs actifs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Filtrer les fournisseurs inactifs.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope: Rechercher par nom.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Vérifie si le fournisseur est actif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Retourne le nombre total de commandes.
     */
    public function getTotalOrdersAttribute(): int
    {
        return $this->purchaseOrders()->count();
    }
}
