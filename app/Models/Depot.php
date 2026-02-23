<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $code
 * @property bool $is_active
 */
class Depot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_depot')->withTimestamps();
    }

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
