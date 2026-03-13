<?php

namespace Src\Infrastructure\GlobalCommerce\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $shop_id
 * @property string $supplier_id
 * @property string $status
 * @property float $total_amount
 * @property string $currency
 * @property \Carbon\Carbon|null $expected_at
 * @property \Carbon\Carbon|null $received_at
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Illuminate\Database\Eloquent\Collection<int, PurchaseLineModel> $lines
 *
 * // Champs agrégés utilisés dans les rapports (SELECT ... AS)
 * @property-read string|null $date
 * @property-read int|null $count
 * @property-read float|null $total
 */
class PurchaseModel extends Model
{
    protected $table = 'gc_purchases';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'shop_id', 'supplier_id', 'status', 'total_amount', 'currency',
        'expected_at', 'received_at', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'expected_at' => 'date',
        'received_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PurchaseLineModel::class, 'purchase_id');
    }

    public function supplier()
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }
}
