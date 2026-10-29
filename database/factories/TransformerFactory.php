<?php

namespace Database\Factories;

use App\Models\Transformer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transformer> */
class TransformerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'company_name' => fake()->company().' Transformation',
            'location' => fake()->city().', France',
            'latitude' => fake()->latitude(41, 51),
            'longitude' => fake()->longitude(-5, 8),
            'transformation_type' => fake()->randomElement(['meunerie', 'conserve', 'laiterie', 'huile', 'boulangerie']),
            'process_description' => fake()->paragraph(),
            'production_capacity' => fake()->numberBetween(50, 2000).' tonnes/an',
            'certifications' => fake()->randomElements(['IFS', 'BRC', 'ISO22000'], rand(0, 2)),
        ];
    }

    public function withUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state(['role' => 'transformer']),
        ]);
    }
}
