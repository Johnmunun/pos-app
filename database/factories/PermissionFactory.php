<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('perm_????.????'),
            'description' => $this->faker->sentence(6),
            'group' => 'general',
            'is_old' => false,
        ];
    }
}

