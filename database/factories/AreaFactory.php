<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->streetName(),
            'region_id' => Region::factory(),
        ];
    }
}
