<?php

namespace Database\Factories;

use App\Models\Producer;
use App\Models\Product;
use App\Models\Transformer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'barcode' => fake()->unique()->ean13(),
            'sku' => strtoupper(fake()->unique()->bothify('TV-####-??')),
            'category' => fake()->randomElement(['fruits', 'légumes', 'céréales', 'produits laitiers', 'boissons', 'épicerie']),
            'origin' => fake()->city().', France',
            'ingredients' => fake()->sentence(8),
            'producer_id' => Producer::factory(),
            'transformer_id' => fake()->boolean(70) ? Transformer::factory() : null,
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published']);
    }
}
