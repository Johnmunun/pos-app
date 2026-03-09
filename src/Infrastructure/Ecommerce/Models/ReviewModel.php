<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_reviews';

    protected $fillable = [
        'shop_id',
        'product_id',
        'customer_id',
        'order_id',
        'customer_name',
        'customer_email',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
    ];
}
