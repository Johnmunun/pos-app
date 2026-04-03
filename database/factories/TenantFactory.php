<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $email = $this->faker->unique()->safeEmail();
        $code = strtoupper(Str::replace([' ', '.'], '', $this->faker->unique()->bothify('TEN###')));

        return [
            'name' => $name,
            'code' => $code,
            'email' => $email,
            'is_active' => true,
            'sector' => 'pharmacy',
            'slug' => Str::slug($name) . '-' . Str::lower($this->faker->unique()->randomLetter()),
        ];
    }
}

