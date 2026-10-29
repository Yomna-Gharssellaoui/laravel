<?php

namespace Database\Seeders;

use App\Services\RecommendationEngine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with the Trace Verte demo dataset.
     * Accounts and test scenarios: docs/GUIDE-DE-TEST.md (password: "password").
     */
    public function run(): void
    {
        $this->call(TraceVerteDatasetSeeder::class);

        // Personalised scores for every consumer profile
        app(RecommendationEngine::class)->recomputeAll();
    }
}
