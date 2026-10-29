<?php

namespace Tests\Feature;

use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\AiProvider;
use App\Services\Ai\ClaudeModel;
use App\Services\Ai\GeminiModel;
use App\Services\GreenwashingAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gemini responses are simulated with Http::fake(): no real API call.
 */
class GeminiProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);
    }

    private function geminiReturns(string $text): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $text]]], 'finishReason' => 'STOP']],
            ]),
        ]);
    }

    private function claim(): EnvironmentalClaim
    {
        return EnvironmentalClaim::create([
            'product_id' => Product::factory()->create()->id,
            'claim_type' => 'Neutre en carbone',
            'title' => 'Produit 100% neutre en carbone',
            'status' => 'pending',
        ]);
    }

    public function test_provider_selection(): void
    {
        $this->assertInstanceOf(GeminiModel::class, app(AiProvider::class)->current());

        config(['services.ai.provider' => 'auto', 'services.anthropic.api_key' => 'sk-test']);
        $this->assertInstanceOf(ClaudeModel::class, app(AiProvider::class)->current(), 'auto prefers Claude when both keys exist');

        config(['services.anthropic.api_key' => null]);
        $this->assertInstanceOf(GeminiModel::class, app(AiProvider::class)->current());

        config(['services.gemini.api_key' => null]);
        $this->assertNull(app(AiProvider::class)->current());
    }

    public function test_greenwashing_analysis_uses_gemini_structured_output(): void
    {
        $this->geminiReturns(json_encode([
            'greenwashing_score' => 82,
            'credibility_rating' => 'faible',
            'summary' => 'Allégation de neutralité carbone sans bilan ni compensation.',
            'red_flags' => ['Aucun bilan carbone', 'Terme « neutre en carbone » non étayé'],
        ]));

        $analysis = app(GreenwashingAnalyzer::class)->analyzeClaim($this->claim());

        $this->assertSame('gemini-2.5-flash', $analysis->model_used);
        $this->assertFalse($analysis->is_demo);
        $this->assertSame(82, $analysis->greenwashing_score);
        $this->assertCount(2, $analysis->red_flags);

        Http::assertSent(function (Request $request) {
            $schema = $request['generationConfig']['responseSchema'];

            return $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && ! array_key_exists('additionalProperties', $schema)
                && $schema['type'] === 'OBJECT'
                && $schema['properties']['red_flags']['items']['type'] === 'STRING';
        });
    }

    public function test_gemini_error_falls_back_to_the_heuristic(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 400)]);

        $analysis = app(GreenwashingAnalyzer::class)->analyzeClaim($this->claim());

        $this->assertTrue($analysis->is_demo);
        $this->assertSame(GreenwashingAnalyzer::HEURISTIC_MODEL, $analysis->model_used);
    }

    public function test_incomplete_json_falls_back_to_the_heuristic(): void
    {
        $this->geminiReturns('{"summary": "incomplet"}');

        $this->assertTrue(app(GreenwashingAnalyzer::class)->analyzeClaim($this->claim())->is_demo);
    }

    public function test_product_assistant_answers_with_gemini(): void
    {
        $this->geminiReturns('Ce produit vient de Sfax, en Tunisie.');
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs(User::factory()->create())
            ->postJson("/produits/{$product->id}/assistant", ['question' => 'D’où vient ce produit ?'])
            ->assertOk()
            ->assertJson(['ai' => true, 'answer' => 'Ce produit vient de Sfax, en Tunisie.']);

        Http::assertSent(fn (Request $request) => str_contains($request['contents'][0]['parts'][0]['text'], '<fiche_produit>'));
    }
}
