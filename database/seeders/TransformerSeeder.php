<?php

namespace Database\Seeders;

use App\Models\Transformer;
use Illuminate\Database\Seeder;

class TransformerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transformer::factory()
            ->count(6)
            ->withUser()
            ->create();
    }
}
