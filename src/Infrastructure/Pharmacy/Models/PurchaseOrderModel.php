<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderModel extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_purchase_orders';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'supplier_id',
        'status',
        'total_amount',
        'currency',
        'ordered_at',
        'expected_at',
        'received_at',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'expected_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_id');
    }
}

