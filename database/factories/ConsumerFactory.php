<?php

namespace Database\Factories;

use App\Models\Consumer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Consumer> */
class ConsumerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'consumer']),
            'preferences' => fake()->randomElements(['local', 'bio', 'faible_co2', 'équitable', 'saison'], rand(1, 3)),
            'sustainability_level' => fake()->randomElement(['débutant', 'intermédiaire', 'engagé', 'expert']),
            'dietary_restrictions' => fake()->randomElements(['végétarien', 'végétalien', 'sans gluten', 'halal'], rand(0, 2)),
            'allergies' => fake()->randomElements(['gluten', 'lactose', 'arachides', 'fruits à coque'], rand(0, 2)),
            'budget_range' => fake()->randomElement(['éco', 'moyen', 'premium']),
        ];
    }
}
