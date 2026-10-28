<?php

namespace Database\Seeders;

use App\Models\AIAnalysis;
use Illuminate\Database\Seeder;

class AIAnalysisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AIAnalysis::factory()->count(20)->create();
    }
}
