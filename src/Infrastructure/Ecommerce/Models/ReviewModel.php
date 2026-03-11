<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $shop_id
 * @property string $product_id
 * @property string|null $customer_id
 * @property string|null $order_id
 * @property string|null $customer_name
 * @property string|null $customer_email
 * @property int $rating
 * @property string|null $title
 * @property string|null $comment
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property bool $is_featured
 * @property array<int,string>|null $images
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
