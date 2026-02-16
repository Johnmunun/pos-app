<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleLineModel extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_sale_lines';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'quantity',
        'unit_price_amount',
        'currency',
        'line_total_amount',
        'discount_percent',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_amount' => 'decimal:2',
        'line_total_amount' => 'decimal:2',
        'discount_percent' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(SaleModel::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}

