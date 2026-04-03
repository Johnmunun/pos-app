<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => 'Role ' . Str::upper($this->faker->unique()->lexify('R???')),
            'description' => $this->faker->sentence(6),
            'is_active' => true,
        ];
    }
}

