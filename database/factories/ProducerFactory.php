<?php

namespace Database\Factories;

use App\Models\Producer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Producer> */
class ProducerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'company_name' => fake()->company().' Ferme',
            'location' => fake()->city().', France',
            'latitude' => fake()->latitude(41, 51),
            'longitude' => fake()->longitude(-5, 8),
            'farming_method' => fake()->randomElement(['biologique', 'raisonnée', 'permaculture', 'conventionnelle']),
            'crop_types' => fake()->randomElements(['blé', 'maïs', 'légumes', 'fruits', 'légumineuses'], rand(1, 3)),
            'production_capacity' => fake()->numberBetween(10, 500).' tonnes/an',
            'certifications' => fake()->randomElements(['AB', 'HVE', 'GlobalGAP'], rand(0, 2)),
        ];
    }

    public function withUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state(['role' => 'producer']),
        ]);
    }
}
