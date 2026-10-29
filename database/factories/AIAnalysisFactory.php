<?php

namespace Database\Factories;

use App\Models\AIAnalysis;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AIAnalysis> */
class AIAnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'analysis_type' => fake()->randomElement(['greenwashing_scan', 'credibility_check', 'label_consistency']),
            'greenwashing_score' => fake()->numberBetween(0, 100),
            'credibility_rating' => fake()->randomElement(['faible', 'moyenne', 'élevée']),
            'ai_summary' => '[Démonstration] '.fake()->paragraph(),
            'model_used' => fake()->randomElement(['demo-rules-v1', 'demo-heuristic-v2']),
            'is_demo' => true,
        ];
    }
}
