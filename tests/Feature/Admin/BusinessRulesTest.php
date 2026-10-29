<?php

namespace Tests\Feature\Admin;

use App\Models\Alert;
use App\Models\Certificate;
use App\Models\ComplianceCheck;
use App\Models\EnvironmentalClaim;
use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupplyChainTrace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    private function claim(Product $product): EnvironmentalClaim
    {
        return EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Neutre en carbone',
            'title' => 'Produit 100% écologique',
            'status' => 'pending',
        ]);
    }

    public function test_failed_compliance_check_creates_an_alert_and_rejects_the_claim(): void
    {
        $product = Product::factory()->create();
        $claim = $this->claim($product);

        $this->actingAs($this->admin)->post('/admin/compliance-checks', [
            'environmental_claim_id' => $claim->id,
            'check_type' => 'document_review',
            'status' => 'failed',
            'result_summary' => 'Aucun justificatif.',
        ])->assertRedirect();

        $check = ComplianceCheck::firstOrFail();
        $this->assertSame($product->id, $check->product_id, 'The product must come from the claim');
        $this->assertSame('rejected', $claim->fresh()->status);
        $this->assertDatabaseHas('alerts', [
            'compliance_check_id' => $check->id,
            'product_id' => $product->id,
            'severity' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_passed_checks_verify_the_claim(): void
    {
        $claim = $this->claim(Product::factory()->create());

        $this->actingAs($this->admin)->post('/admin/compliance-checks', [
            'environmental_claim_id' => $claim->id,
            'check_type' => 'lab_test',
            'status' => 'passed',
            'result_summary' => 'Conforme.',
        ]);

        $this->assertSame('verified', $claim->fresh()->status);
    }

    public function test_statuses_are_restricted_to_known_values(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post('/admin/environmental-claims', [
            'product_id' => $product->id,
            'claim_type' => 'Local',
            'title' => 'Local',
            'status' => 'n_importe_quoi',
        ])->assertSessionHasErrors('status');
    }

    public function test_resolving_an_alert_requires_a_resolution(): void
    {
        $alert = Alert::factory()->create(['status' => 'open']);

        $payload = [
            'product_id' => $alert->product_id,
            'alert_type' => 'greenwashing',
            'severity' => 'medium',
            'title' => 'Titre',
            'status' => 'resolved',
        ];

        $this->actingAs($this->admin)->put("/admin/alerts/{$alert->id}", $payload)->assertSessionHasErrors('resolution');
        $this->actingAs($this->admin)->put("/admin/alerts/{$alert->id}", $payload + ['resolution' => 'Étiquette corrigée'])->assertSessionHasNoErrors();
    }

    public function test_footprint_score_is_computed_not_typed(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post('/admin/environmental-footprints', [
            'product_id' => $product->id,
            'co2_emissions' => 2,     // 90/100
            'water_usage' => 50,      // 90/100
            'land_usage' => 1,        // 90/100
            'ai_score' => 3,          // ignored
        ])->assertRedirect();

        $this->assertSame(90, EnvironmentalFootprint::firstOrFail()->ai_score);
    }

    public function test_product_cannot_be_published_without_footprint_and_certificate(): void
    {
        $product = Product::factory()->create(['status' => 'draft']);
        $payload = ['name' => $product->name, 'status' => 'published'];

        $this->actingAs($this->admin)->put("/admin/products/{$product->id}", $payload)->assertSessionHasErrors('status');

        EnvironmentalFootprint::factory()->for($product)->create();
        Certificate::factory()->for($product)->create(['status' => 'verified']);

        $this->actingAs($this->admin)->put("/admin/products/{$product->id}", $payload)->assertSessionHasNoErrors();
        $this->assertSame('published', $product->fresh()->status);
    }

    public function test_certificate_number_is_unique_per_issuer(): void
    {
        $existing = Certificate::factory()->create(['issuer' => 'Ecocert', 'certificate_number' => 'ABC-1']);

        $payload = [
            'product_id' => $existing->product_id,
            'certificate_type' => 'Bio AB',
            'certificate_number' => 'ABC-1',
            'status' => 'pending',
        ];

        $this->actingAs($this->admin)->post('/admin/certificates', $payload + ['issuer' => 'Ecocert'])->assertSessionHasErrors('certificate_number');
        $this->actingAs($this->admin)->post('/admin/certificates', $payload + ['issuer' => 'Bureau Veritas'])->assertSessionHasNoErrors();
    }

    public function test_trace_steps_set_current_position_and_distance(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post('/admin/supply-chain-traces', [
            'product_id' => $product->id,
            'status' => 'in_transit',
            'steps' => [
                ['stage' => 'transport', 'location' => 'Lyon', 'at' => '2026-10-02T10:00', 'lat' => 45.764, 'lon' => 4.8357],
                ['stage' => 'production', 'location' => 'Paris', 'at' => '2026-10-01T08:00', 'lat' => 48.8566, 'lon' => 2.3522],
            ],
        ])->assertRedirect();

        $trace = SupplyChainTrace::firstOrFail();
        $this->assertSame('transport', $trace->current_stage, 'Steps are sorted by date, the last one is current');
        $this->assertEqualsWithDelta(392, (float) $trace->total_distance_km, 5);
        $this->assertCount(2, $trace->path_history);
    }

    public function test_scan_command_expires_certificates_and_raises_alerts(): void
    {
        $certificate = Certificate::factory()->create([
            'status' => 'verified',
            'issue_date' => now()->subYears(3),
            'expiry_date' => now()->subDay(),
        ]);

        $this->artisan('traceverte:scan-alerts')->assertSuccessful();
        $this->artisan('traceverte:scan-alerts')->assertSuccessful(); // no duplicate

        $this->assertSame('expired', $certificate->fresh()->status);
        $this->assertSame(1, Alert::where('product_id', $certificate->product_id)->where('alert_type', 'certificate_expired')->count());
    }

    public function test_consumer_review_goes_through_moderation(): void
    {
        $consumer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs($consumer)->post("/produits/{$product->id}/avis", ['rating' => 4, 'comment' => 'Très bon'])->assertRedirect();
        $review = Review::firstOrFail();
        $this->assertSame('pending', $review->status);

        $this->actingAs($consumer)->patch("/admin/reviews/{$review->id}/moderate", ['status' => 'approved'])->assertForbidden();
        $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/moderate", ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $review->fresh()->status);
    }

    public function test_actor_profile_cannot_be_linked_to_an_account_with_another_role(): void
    {
        $consumer = User::factory()->create();

        $this->actingAs($this->admin)->post('/admin/producers', [
            'company_name' => 'Ferme',
            'user_id' => $consumer->id,
        ])->assertSessionHasErrors('user_id');
    }
}
