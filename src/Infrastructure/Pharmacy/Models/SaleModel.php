<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $shop_id
 * @property int|null $customer_id
 * @property string $status
 * @property float|string $total_amount
 * @property float|string $paid_amount
 * @property float|string $balance_amount
 * @property string $currency
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property int|null $cash_register_id
 * @property int|null $cash_register_session_id
 * @property string|null $sale_type
 */
class SaleModel extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_sales';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'customer_id',
        'cash_register_id',
        'cash_register_session_id',
        'status',
        'sale_type',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'currency',
        'created_by',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'depot_id' => 'integer',
    ];

    public function lines()
    {
        return $this->hasMany(SaleLineModel::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_id');
    }

    public function depot()
    {
        return $this->belongsTo(\App\Models\Depot::class, 'depot_id');
    }

    /** Utilisateur qui a créé la vente (vendeur). */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

