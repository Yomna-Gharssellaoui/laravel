<?php

namespace Database\Seeders;

use App\Models\Producer;
use App\Models\Product;
use App\Models\Transformer;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $producers = Producer::all();
        $transformers = Transformer::all();

        if ($producers->isEmpty()) {
            Producer::factory()->count(12)->withUser()->create();
            $producers = Producer::all();
        }

        // Create a mix of published and draft products tied to existing producers/transformers.
        for ($i = 0; $i < 40; $i++) {
            $producer = $producers->random();
            $transformer = $transformers->isNotEmpty() && rand(0, 100) < 70 ? $transformers->random() : null;

            Product::factory()
                ->for($producer, 'producer')
                ->state([
                    'transformer_id' => $transformer?->id,
                    'status' => ($i % 2 === 0) ? 'published' : 'draft',
                ])
                ->create();
        }
    }
}
