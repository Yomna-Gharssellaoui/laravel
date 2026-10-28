<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Alert> */
class AlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'alert_type' => fake()->randomElement(['greenwashing', 'label_incoherent', 'origine_douteuse', 'empreinte_élevée']),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'detected_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => fake()->randomElement(['open', 'investigating', 'resolved', 'dismissed']),
            'resolution' => null,
        ];
    }
}
