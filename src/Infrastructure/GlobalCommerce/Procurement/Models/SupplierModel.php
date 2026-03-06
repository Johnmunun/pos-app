<?php

namespace Src\Infrastructure\GlobalCommerce\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $shop_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property bool $is_active
 */
class SupplierModel extends Model
{
    protected $table = 'gc_suppliers';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'shop_id', 'name', 'email', 'phone', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
