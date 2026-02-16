<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: SupplierProductPriceModel
 *
 * Eloquent Model pour la table des prix fournisseur-produit.
 *
 * @property string $id
 * @property string $supplier_id
 * @property string $product_id
 * @property float $normal_price
 * @property float|null $agreed_price
 * @property float $tax_rate
 * @property \Carbon\Carbon $effective_from
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read SupplierModel $supplier
 * @property-read ProductModel $product
 * @property-read float $effective_price
 * @property-read float $price_with_tax
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @method static static|null find(string $id)
 * @method static static create(array<string, mixed> $attributes)
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string $column, mixed $operator = null, mixed $value = null)
 */
class SupplierProductPriceModel extends Model
{
    protected $table = 'pharmacy_supplier_product_prices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'supplier_id',
        'product_id',
        'normal_price',
        'agreed_price',
        'tax_rate',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'normal_price' => 'float',
        'agreed_price' => 'float',
        'tax_rate' => 'float',
        'effective_from' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: Appartient à un fournisseur.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    /**
     * Relation: Appartient à un produit.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    /**
     * Retourne le prix effectif.
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->agreed_price ?? $this->normal_price;
    }

    /**
     * Calcule le prix TTC.
     */
    public function getPriceWithTaxAttribute(): float
    {
        $price = $this->effective_price;
        return round($price * (1 + $this->tax_rate / 100), 2);
    }
}
