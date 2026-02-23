<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'status',
        'logo',
        'sector',
        'slug',
        'business_type',
        'legal_form',
        'registration_number',
        'tax_id',
        'currency_code',
        'timezone',
        'locale',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Get the users for the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the shops for the tenant.
     */
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    /**
     * Get the depots for the tenant.
     */
    public function depots()
    {
        return $this->hasMany(Depot::class);
    }
}