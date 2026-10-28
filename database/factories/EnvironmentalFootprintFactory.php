<?php

namespace Database\Factories;

use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnvironmentalFootprint> */
class EnvironmentalFootprintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'co2_emissions' => fake()->randomFloat(2, 0.1, 25),
            'water_usage' => fake()->randomFloat(2, 10, 5000),
            'land_usage' => fake()->randomFloat(2, 0.01, 12),
            'ai_score' => fake()->numberBetween(10, 95),
        ];
    }
}
