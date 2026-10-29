<?php

namespace Database\Seeders;

use App\Models\PersonalRating;
use Illuminate\Database\Seeder;

class PersonalRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PersonalRating::factory()->count(30)->create();
    }
}
