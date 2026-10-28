<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\SupplyChainTrace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyChainTrace> */
class SupplyChainTraceFactory extends Factory
{
    public function definition(): array
    {
        $stages = ['production', 'transformation', 'stockage', 'transport', 'distribution', 'vente'];
        $current = fake()->randomElement($stages);

        return [
            'product_id' => Product::factory(),
            'distributor_id' => Distributor::factory(),
            'current_stage' => $current,
            'current_location_lat' => fake()->latitude(41, 51),
            'current_location_lon' => fake()->longitude(-5, 8),
            'path_history' => [
                ['stage' => 'production', 'location' => fake()->city(), 'at' => now()->subDays(10)->toIso8601String()],
                ['stage' => $current, 'location' => fake()->city(), 'at' => now()->subDays(2)->toIso8601String()],
            ],
            'status' => fake()->randomElement(['in_transit', 'delivered', 'delayed', 'completed']),
            'total_distance_km' => fake()->randomFloat(2, 5, 1200),
        ];
    }
}
