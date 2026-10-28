<?php

namespace Database\Seeders;

use App\Models\Producer;
use Illuminate\Database\Seeder;

class ProducerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a handful of producers with associated user accounts
        Producer::factory()
            ->count(12)
            ->withUser()
            ->create();
    }
}
