<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderLineModel extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_purchase_order_lines';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'purchase_order_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
        'unit_cost_amount',
        'currency',
        'line_total_amount',
    ];

    protected $casts = [
        'ordered_quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_cost_amount' => 'decimal:2',
        'line_total_amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderModel::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}

