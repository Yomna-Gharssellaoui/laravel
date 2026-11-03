<?php

namespace Tests\Feature\Admin;

use App\Models\AIAnalysis;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Models\User;
use App\Services\GreenwashingAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_analyses_by_product_name_query(): void
    {
        $productA = Product::factory()->create(['name' => 'Pommes Bio']);
        $productB = Product::factory()->create(['name' => 'Lait Frais']);

        $analysisA = AIAnalysis::factory()->create(['product_id' => $productA->id]);
        $analysisB = AIAnalysis::factory()->create(['product_id' => $productB->id]);

        $this->actingAs($this->admin)->get('/admin/ai-analyses?q=Pommes')
            ->assertOk()
            ->assertSee('Pommes Bio')
            ->assertDontSee('Lait Frais');
    }

    public function test_admin_can_filter_analyses_by_analysis_type(): void
    {
        $product = Product::factory()->create(['name' => 'Miel Artisanal']);

        $analysis1 = AIAnalysis::factory()->create([
            'product_id' => $product->id,
            'analysis_type' => 'greenwashing_scan',
        ]);
        $analysis2 = AIAnalysis::factory()->create([
            'product_id' => $product->id,
            'analysis_type' => 'credibility_check',
        ]);

        $this->actingAs($this->admin)->get('/admin/ai-analyses?analysis_type=greenwashing_scan')
            ->assertOk()
            ->assertSee('greenwashing_scan')
            ->assertDontSee('credibility_check');
    }

    public function test_admin_can_view_single_analysis(): void
    {
        $product = Product::factory()->create(['name' => 'Jus de Pomme']);
        $analysis = AIAnalysis::factory()->create(['product_id' => $product->id]);

        $this->actingAs($this->admin)->get("/admin/ai-analyses/{$analysis->id}")
            ->assertOk()
            ->assertSee('Jus de Pomme');
    }

    public function test_admin_can_delete_an_analysis(): void
    {
        $analysis = AIAnalysis::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/ai-analyses/{$analysis->id}")
            ->assertRedirect(route('admin.ai-analyses.index'))
            ->assertSessionHas('success', 'Analyse supprimée.');

        $this->assertModelMissing($analysis);
    }

    public function test_admin_can_launch_analysis_for_a_product(): void
    {
        config(['services.anthropic.api_key' => null]);
        $product = Product::factory()->create();

        EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Produit respectueux de l’environnement',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/products/{$product->id}/analyze");

        $analysis = AIAnalysis::where('product_id', $product->id)->latest()->firstOrFail();

        $response->assertRedirect(route('admin.ai-analyses.show', $analysis))
            ->assertSessionHas('success');

        $this->assertSame('produit', $analysis->analysis_type);
        $this->assertDatabaseHas('ai_analyses', ['id' => $analysis->id, 'product_id' => $product->id]);
    }

    public function test_admin_can_launch_analysis_for_a_claim(): void
    {
        config(['services.anthropic.api_key' => null]);
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Produit local',
            'title' => 'Récolté à 20 km',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/environmental-claims/{$claim->id}/analyze");

        $analysis = AIAnalysis::where('environmental_claim_id', $claim->id)->firstOrFail();

        $response->assertRedirect(route('admin.ai-analyses.show', $analysis))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ai_analyses', ['id' => $analysis->id, 'environmental_claim_id' => $claim->id]);
    }

    public function test_analyze_claim_aborts_when_claim_has_no_product(): void
    {
        $claim = EnvironmentalClaim::create([
            'product_id' => null,
            'claim_type' => 'Sans produit',
            'title' => 'Orphan claim',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->post("/admin/environmental-claims/{$claim->id}/analyze")
            ->assertStatus(422);
    }

    public function test_analysis_message_displays_ai_model_name_when_not_demo(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Local',
            'title' => 'Produit local',
            'status' => 'pending',
        ]);

        $fakeAnalysis = AIAnalysis::factory()->create([
            'product_id' => $product->id,
            'environmental_claim_id' => $claim->id,
            'is_demo' => false,
            'model_used' => 'claude-3-5-sonnet',
        ]);

        $mockAnalyzer = $this->createMock(GreenwashingAnalyzer::class);
        $mockAnalyzer->expects($this->once())
            ->method('analyzeProduct')
            ->willReturn($fakeAnalysis);

        $this->app->instance(GreenwashingAnalyzer::class, $mockAnalyzer);

        $this->actingAs($this->admin)->post("/admin/products/{$product->id}/analyze")
            ->assertRedirect(route('admin.ai-analyses.show', $fakeAnalysis))
            ->assertSessionHas('success', 'Analyse IA enregistrée (claude-3-5-sonnet).');
    }
}
