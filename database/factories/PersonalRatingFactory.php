<?php

namespace Database\Factories;

use App\Models\Consumer;
use App\Models\PersonalRating;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PersonalRating> */
class PersonalRatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consumer_id' => Consumer::factory(),
            'product_id' => Product::factory(),
            'personalized_score' => fake()->numberBetween(20, 98),
            'reason' => fake()->sentence(),
            'recommendation_reason' => 'Correspond à vos préférences enregistrées (démonstration).',
        ];
    }
}
