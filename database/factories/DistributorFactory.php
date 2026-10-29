<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Distributor> */
class DistributorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'company_name' => fake()->company().' Distribution',
            'distributor_type' => fake()->randomElement(['grossiste', 'retail', 'coopérative', 'e-commerce']),
            'location' => fake()->city().', France',
            'latitude' => fake()->latitude(41, 51),
            'longitude' => fake()->longitude(-5, 8),
            'coverage_area' => fake()->randomElement(['régional', 'national', 'europe']),
        ];
    }

    public function withUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state(['role' => 'distributor']),
        ]);
    }
}
