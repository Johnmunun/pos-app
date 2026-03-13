<?php

namespace Src\Infrastructure\GlobalCommerce\Sales\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $shop_id
 * @property string $status
 * @property float $total_amount
 * @property string $currency
 * @property string|null $customer_name
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Illuminate\Database\Eloquent\Collection<int, SaleLineModel> $lines
 * @property \App\Models\User|null $creator
 *
 * // Champs agrégés utilisés dans les rapports (SELECT ... AS)
 * @property-read string|null $date
 * @property-read int|null $count
 * @property-read float|null $total
 */
class SaleModel extends Model
{
    protected $table = 'gc_sales';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'status',
        'total_amount',
        'currency',
        'customer_name',
        'notes',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected $casts = [
        'total_amount' => 'float',
    ];

    public function lines()
    {
        return $this->hasMany(SaleLineModel::class, 'sale_id');
    }
}
