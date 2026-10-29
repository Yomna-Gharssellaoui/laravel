<?php

namespace Database\Seeders;

use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use Illuminate\Database\Seeder;

class EnvironmentalFootprintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::where('status', 'published')->get();

        if ($products->isEmpty()) {
            // Fallback: create some footprints normally
            EnvironmentalFootprint::factory()->count(12)->create();

            return;
        }

        foreach ($products as $product) {
            if ($product->environmentalFootprint) {
                continue;
            }

            EnvironmentalFootprint::factory()
                ->for($product, 'product')
                ->create();
        }
    }
}
