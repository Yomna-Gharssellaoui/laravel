<?php

namespace Tests\Feature;

use App\Models\Consumer;
use App\Models\EnvironmentalClaim;
use App\Models\EnvironmentalFootprint;
use App\Models\PersonalRating;
use App\Models\Producer;
use App\Models\Product;
use App\Models\SupplyChainTrace;
use App\Models\User;
use App\Services\GreenwashingAnalyzer;
use App\Services\Ml\MlService;
use App\Services\RecommendationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Laravel side of the local ML service (ml/): the HTTP calls are faked, the Python models
 * themselves are tested by ml/tests (pytest).
 */
class MachineLearningTest extends TestCase
{
    use RefreshDatabase;

    private function enableMl(array $responses): void
    {
        config(['services.ml.url' => 'http://ml.test', 'services.anthropic.api_key' => null, 'services.gemini.api_key' => null]);
        Http::fake(collect($responses)->mapWithKeys(fn ($body, $path) => ["ml.test$path" => Http::response($body)])->all());
    }

    public function test_service_is_disabled_without_url(): void
    {
        Http::fake();

        $this->assertFalse(app(MlService::class)->enabled());
        $this->assertNull(app(MlService::class)->reviewAnalysis('Très bon produit'));
        Http::assertNothingSent();
    }

    public function test_a_stopped_service_returns_null_and_is_not_retried_for_30_seconds(): void
    {
        config(['services.ml.url' => 'http://ml.test']);
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('Connection refused');
        });

        $ml = app(MlService::class);
        $this->assertNull($ml->reviewAnalysis('Très bon produit'));
        $this->assertNull($ml->greenwashing('100 % naturel'));

        $this->assertSame(1, $attempts);
    }

    public function test_capacity_strings_are_converted_to_tonnes(): void
    {
        $this->assertSame(120.0, MlService::capacityInTonnes('120 tonnes/an'));
        $this->assertSame(2000.0, MlService::capacityInTonnes('2 000 tonnes/an'));
        $this->assertSame(1500.0, MlService::capacityInTonnes('1,5 million L/an'));
        $this->assertSame(0.8, MlService::capacityInTonnes('800 kg'));
        $this->assertSame(0.0, MlService::capacityInTonnes(null));
    }

    public function test_product_features_are_sent_to_the_footprint_model(): void
    {
        $this->enableMl(['/m1/footprint' => ['co2_emissions' => ['value' => 2.1, 'low' => 1.5, 'high' => 2.9]]]);
        $product = Product::factory()->create(['category' => 'huiles']);

        $estimate = app(MlService::class)->footprintEstimate($product);

        $this->assertSame(2.1, $estimate['co2_emissions']['value']);
        Http::assertSent(fn (Request $r) => $r->url() === 'http://ml.test/m1/footprint' && $r['category'] === 'huiles');
    }

    public function test_predictions_are_cached(): void
    {
        $this->enableMl(['/m4/review' => ['sentiment' => 'positif', 'spam_probability' => 1]]);
        $ml = app(MlService::class);

        $ml->reviewAnalysis('Très bon produit');
        $ml->reviewAnalysis('Très bon produit');

        Http::assertSentCount(1);
    }

    public function test_a_review_the_model_flags_as_spam_is_rejected(): void
    {
        $this->enableMl(['/m4/review' => ['sentiment' => 'neutre', 'spam_probability' => 97.5, 'is_spam' => true]]);
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5, 'comment' => 'Gagnez un iPhone, cliquez ici http://promo.example',
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'status' => 'rejected']);
    }

    public function test_a_normal_review_waits_for_moderation_even_without_the_service(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $this->actingAs($user)->post(route('reviews.store', $product), ['rating' => 4, 'comment' => 'Bon goût'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'status' => 'pending']);
    }

    public function test_greenwashing_analysis_uses_the_ml_model_first(): void
    {
        $this->enableMl(['/m5/greenwashing' => ['probability' => 96.0, 'is_greenwashing' => true, 'risky_terms' => ['planète'], 'reassuring_terms' => []]]);
        $claim = EnvironmentalClaim::create([
            'product_id' => Product::factory()->create()->id,
            'claim_type' => '100% écologique',
            'title' => 'Respectueux de la planète',
            'status' => 'pending',
        ]);

        $analysis = app(GreenwashingAnalyzer::class)->analyzeClaim($claim);

        $this->assertSame(GreenwashingAnalyzer::ML_MODEL, $analysis->model_used);
        $this->assertGreaterThanOrEqual(65, $analysis->greenwashing_score);
        $this->assertContains('Expression à risque repérée par le modèle : « planète »', $analysis->red_flags);
    }

    public function test_collaborative_predictions_are_blended_into_recommendations(): void
    {
        $user = User::factory()->create();
        $consumer = Consumer::factory()->for($user)->create(['preferences' => [], 'allergies' => []]);
        $liked = Product::factory()->published()->create(['sku' => 'TV-MIE-005', 'ingredients' => 'Miel']);
        $disliked = Product::factory()->published()->create(['sku' => 'TV-LEB-006', 'ingredients' => 'Lait']);

        app(RecommendationEngine::class)->recompute($consumer);
        $before = PersonalRating::where('consumer_id', $consumer->id)->pluck('personalized_score', 'product_id');

        $this->enableMl(['/m6/recommendations' => ['predictions' => [
            'TV-MIE-005' => ['rating' => 4.8, 'neighbours' => 20],
            'TV-LEB-006' => ['rating' => 1.4, 'neighbours' => 20],
        ]]]);
        app(RecommendationEngine::class)->recompute($consumer);
        $after = PersonalRating::where('consumer_id', $consumer->id)->pluck('personalized_score', 'product_id');

        $this->assertGreaterThan($after[$disliked->id], $after[$liked->id]);
        $this->assertLessThan($before[$disliked->id], $after[$disliked->id]);
    }

    public function test_back_office_pages_show_ml_predictions(): void
    {
        $this->enableMl([
            '/m2/producer-profile' => ['cluster' => 1, 'label' => 'Petite ferme agroécologique', 'description' => 'Petite surface', 'typicality' => 80],
            '/m5/footprint-anomaly' => ['is_anomaly' => true, 'score' => 85.8, 'ratio_to_category_median' => ['co2_emissions' => 6.1, 'water_usage' => 1, 'land_usage' => 1], 'main_indicator' => 'co2_emissions'],
        ]);
        $admin = User::factory()->admin()->create();
        $producer = Producer::factory()->create();
        $footprint = EnvironmentalFootprint::factory()->create();

        $this->actingAs($admin)->get(route('admin.producers.show', $producer))->assertOk()->assertSee('Petite ferme agroécologique');
        $this->actingAs($admin)->get(route('admin.environmental-footprints.show', $footprint))->assertOk()->assertSee('Empreinte atypique');
    }

    public function test_pages_still_work_when_the_service_is_stopped(): void
    {
        config(['services.ml.url' => 'http://ml.test']);
        Http::fake(fn () => throw new ConnectionException('Connection refused'));
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.products.show', Product::factory()->create()))->assertOk();
        $this->actingAs($admin)->get(route('admin.reviews.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.ml-models.index'))->assertOk()->assertSee('Service ML arrêté');
    }

    public function test_only_admins_see_the_ml_models_page(): void
    {
        $this->enableMl([
            '/health' => ['status' => 'ok', 'models' => ['m4_reviews' => true]],
            '/models' => [['name' => 'm4_reviews', 'module' => 'M4', 'title' => 'Analyse des avis', 'algorithm' => 'TF-IDF', 'description' => 'Sentiment', 'dataset_rows' => 4000, 'trained_at' => '2026-10-30T10:00:00', 'metrics' => ['sentiment_accuracy' => 0.989]]],
        ]);

        $this->actingAs(User::factory()->create())->get(route('admin.ml-models.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.ml-models.index'))
            ->assertOk()->assertSee('Analyse des avis')->assertSee('Exactitude sentiment')->assertSee('0.989');
    }

    public function test_ml_check_command_reports_a_disabled_service(): void
    {
        config(['services.ml.url' => '']);

        $this->artisan('traceverte:ml-check')->assertFailed();
    }

    // ---- Consumer AI tools ---------------------------------------------------

    public function test_greenwashing_checker_highlights_risky_terms(): void
    {
        $this->enableMl(['/m5/greenwashing' => ['probability' => 97.0, 'is_greenwashing' => true, 'risky_terms' => ['planète'], 'reassuring_terms' => []]]);

        $this->post(route('ai-tools.greenwashing'), ['claim' => 'Bon pour la planète <img src=x onerror=alert(1)>'])
            ->assertOk()
            ->assertSee('Greenwashing probable')
            ->assertSee('<mark class="rounded bg-red-100 px-0.5 text-red-800">planète</mark>', false)
            ->assertDontSee('<img src=x', false)
            ->assertSee('&lt;img src=x', false);
    }

    public function test_footprint_simulator_estimates_and_suggests_improvements(): void
    {
        config(['services.ml.url' => 'http://ml.test']);
        Http::fake(['ml.test/m1/footprint' => fn (Request $r) => Http::response([
            'co2_emissions' => ['value' => $r['farming_method'] === 'biologique' ? 3.0 : 4.0, 'low' => 3.5, 'high' => 4.5],
            'water_usage' => ['value' => 250, 'low' => 220, 'high' => 280],
            'land_usage' => ['value' => 4, 'low' => 3.5, 'high' => 4.5],
        ])]);
        $oil = Product::factory()->published()->create(['category' => 'huiles', 'name' => 'Huile témoin']);
        EnvironmentalFootprint::factory()->for($oil)->create();

        $this->post(route('ai-tools.footprint'), [
            'category' => 'huiles', 'farming_method' => 'conventionnelle', 'transformation_type' => 'aucune', 'distance_km' => 20,
        ])->assertOk()->assertSee('Passer en agriculture biologique certifiée')->assertSee('−25 %')->assertSee('Huile témoin');
    }

    public function test_footprint_simulator_rejects_unknown_values(): void
    {
        $this->from(route('ai-tools.index'))->post(route('ai-tools.footprint'), [
            'category' => 'voitures', 'farming_method' => 'biologique', 'transformation_type' => 'aucune', 'distance_km' => 10,
        ])->assertSessionHasErrors('category');
    }

    public function test_review_assistant_flags_rating_mismatch_and_spam(): void
    {
        $this->enableMl(['/m4/review' => ['sentiment' => 'négatif', 'confidence' => 91, 'spam_probability' => 80, 'is_spam' => true]]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('ai-tools.review-check'), ['text' => 'Horrible, achetez ailleurs sur promo.example', 'rating' => 5])
            ->assertOk()
            ->assertJson(['available' => true, 'sentiment' => 'négatif'])
            ->assertJsonCount(2, 'tips');
    }

    public function test_review_assistant_requires_login(): void
    {
        $this->postJson(route('ai-tools.review-check'), ['text' => 'Très bon produit, je recommande'])->assertUnauthorized();
    }

    public function test_public_pages_show_ml_insights(): void
    {
        $this->enableMl([
            '/m1/footprint' => ['co2_emissions' => ['value' => 2.4, 'low' => 2, 'high' => 3], 'water_usage' => ['value' => 200, 'low' => 180, 'high' => 220], 'land_usage' => ['value' => 3, 'low' => 2, 'high' => 4]],
            '/m3/delay-risk' => ['risk' => 72.0, 'level' => 'élevé'],
        ]);
        $product = Product::factory()->published()->create();
        SupplyChainTrace::factory()->for($product)->create(['status' => 'in_transit']);

        $this->get(route('products.show', $product))->assertOk()->assertSee('Ce qu’en disent nos modèles IA')->assertSee('2.4 kg CO₂e/kg');
        $this->get(route('products.trace', $product))->assertOk()->assertSee('Risque de retard (IA) : élevé');
    }

    public function test_ai_tools_page_works_without_the_service(): void
    {
        $this->get(route('ai-tools.index'))->assertOk()->assertSee('momentanément indisponibles');
    }
}
