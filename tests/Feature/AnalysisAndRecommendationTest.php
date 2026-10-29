<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Consumer;
use App\Models\EnvironmentalClaim;
use App\Models\EnvironmentalFootprint;
use App\Models\PersonalRating;
use App\Models\Product;
use App\Models\User;
use App\Services\GreenwashingAnalyzer;
use App\Services\RecommendationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisAndRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_heuristic_analysis_flags_vague_unproven_claims(): void
    {
        config(['services.anthropic.api_key' => null]);

        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => '100% écologique',
            'title' => 'Neutre en carbone et naturel',
            'status' => 'pending',
        ]);

        $analysis = app(GreenwashingAnalyzer::class)->analyzeClaim($claim);

        $this->assertTrue($analysis->is_demo);
        $this->assertSame(GreenwashingAnalyzer::HEURISTIC_MODEL, $analysis->model_used);
        $this->assertGreaterThanOrEqual(65, $analysis->greenwashing_score);
        $this->assertSame('faible', $analysis->credibility_rating);
        $this->assertNotEmpty($analysis->red_flags);
        $this->assertSame(100 - $analysis->greenwashing_score, $claim->fresh()->confidence_score);
    }

    public function test_vague_terms_are_matched_as_whole_words(): void
    {
        config(['services.anthropic.api_key' => null]);

        $claim = EnvironmentalClaim::create([
            'product_id' => Product::factory()->create()->id,
            'claim_type' => 'Produit local',
            'title' => 'Piments récoltés à 12 km',
            'status' => 'pending',
        ]);

        $analysis = app(GreenwashingAnalyzer::class)->analyzeClaim($claim);

        $this->assertEmpty(array_filter($analysis->red_flags, fn ($flag) => str_starts_with($flag, 'Termes vagues')));
    }

    public function test_admin_launches_an_analysis_from_a_claim(): void
    {
        config(['services.anthropic.api_key' => null]);
        $admin = User::factory()->admin()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => Product::factory()->create()->id,
            'claim_type' => 'Produit local',
            'title' => 'Récolté à 20 km',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post("/admin/environmental-claims/{$claim->id}/analyze")->assertRedirect();

        $this->assertDatabaseHas('ai_analyses', ['environmental_claim_id' => $claim->id]);
    }

    public function test_recommendations_follow_preferences_and_exclude_allergens(): void
    {
        $user = User::factory()->create();
        $consumer = Consumer::factory()->for($user)->create([
            'preferences' => ['bio', 'faible_co2'],
            'allergies' => ['gluten'],
        ]);

        $organic = Product::factory()->create(['status' => 'published', 'ingredients' => 'Pommes']);
        EnvironmentalFootprint::factory()->for($organic)->create(['co2_emissions' => 1, 'water_usage' => 10, 'land_usage' => 1]);
        Certificate::factory()->for($organic)->create(['certificate_type' => 'Bio AB', 'status' => 'verified']);

        $plain = Product::factory()->create(['status' => 'published', 'ingredients' => 'Pommes']);
        EnvironmentalFootprint::factory()->for($plain)->create(['co2_emissions' => 12, 'water_usage' => 400, 'land_usage' => 8]);

        $withGluten = Product::factory()->create(['status' => 'published', 'ingredients' => 'Farine de blé, Gluten']);

        app(RecommendationEngine::class)->recompute($consumer);

        $score = fn (Product $p) => PersonalRating::where('consumer_id', $consumer->id)->where('product_id', $p->id)->value('personalized_score');

        $this->assertGreaterThan($score($plain), $score($organic));
        $this->assertSame(0, $score($withGluten));

        $this->actingAs($user)->get('/mes-recommandations')->assertOk()->assertSee($organic->name);
    }
}
