<?php

namespace Tests\Feature\Admin;

use App\Models\ComplianceCheck;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceCheckTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_compliance_checks_by_check_type_query(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Produit Biologique',
            'status' => 'pending',
        ]);

        ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'lab_test',
            'status' => 'pending',
        ]);

        ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'site_audit',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get('/admin/compliance-checks?q=lab_test')
            ->assertOk()
            ->assertSee('lab_test')
            ->assertDontSee('site_audit');
    }

    public function test_admin_can_filter_compliance_checks_by_claim_title_query(): void
    {
        $product = Product::factory()->create();
        $claimA = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Recyclage',
            'title' => 'Emballage Zéro Plastique',
            'status' => 'pending',
        ]);
        $claimB = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Carbone',
            'title' => 'Neutre en carbone',
            'status' => 'pending',
        ]);

        ComplianceCheck::create([
            'environmental_claim_id' => $claimA->id,
            'product_id' => $product->id,
            'check_type' => 'document_review',
            'status' => 'pending',
        ]);
        ComplianceCheck::create([
            'environmental_claim_id' => $claimB->id,
            'product_id' => $product->id,
            'check_type' => 'document_review',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get('/admin/compliance-checks?q=Zéro Plastique')
            ->assertOk()
            ->assertSee('Emballage Zéro Plastique')
            ->assertDontSee('Neutre en carbone');
    }

    public function test_admin_can_filter_compliance_checks_by_status(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Audit Test',
            'status' => 'pending',
        ]);

        ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'lab_test',
            'status' => 'pending',
        ]);
        ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'site_audit',
            'status' => 'passed',
            'result_summary' => 'Parfait',
            'checked_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)->get('/admin/compliance-checks?status=passed')
            ->assertOk()
            ->assertSee('site_audit')
            ->assertDontSee('lab_test');
    }

    public function test_admin_create_screen_with_selected_claim(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Claim sélectionnée',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get("/admin/compliance-checks/create?environmental_claim_id={$claim->id}")
            ->assertOk()
            ->assertSee('Claim sélectionnée');
    }

    public function test_admin_can_create_compliance_check(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Produit Bio',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/compliance-checks', [
            'environmental_claim_id' => $claim->id,
            'check_type' => 'site_audit',
            'status' => 'passed',
            'result_summary' => 'Audit sur site validé.',
        ]);

        $check = ComplianceCheck::where('check_type', 'site_audit')->firstOrFail();

        $response->assertRedirect(route('admin.compliance-checks.show', $check))
            ->assertSessionHas('success', 'Vérification de conformité créée.');

        $this->assertSame($product->id, $check->product_id);
        $this->assertEquals(now()->toDateString(), $check->checked_at->toDateString());
    }

    public function test_admin_can_update_compliance_check(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Produit Biologique',
            'status' => 'pending',
        ]);

        $check = ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'document_review',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/compliance-checks/{$check->id}", [
            'environmental_claim_id' => $claim->id,
            'check_type' => 'lab_test',
            'status' => 'passed',
            'result_summary' => 'Conforme aux critères écologiques.',
            'notes' => 'Test en laboratoire réussi.',
        ]);

        $response->assertRedirect(route('admin.compliance-checks.show', $check))
            ->assertSessionHas('success', 'Vérification de conformité mise à jour.');

        $fresh = $check->fresh();
        $this->assertSame('lab_test', $fresh->check_type);
        $this->assertSame('passed', $fresh->status);
        $this->assertSame('Conforme aux critères écologiques.', $fresh->result_summary);
        $this->assertEquals(now()->toDateString(), $fresh->checked_at->toDateString());
    }

    public function test_admin_updating_compliance_check_to_failed_includes_alert_message(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Allégation test',
            'status' => 'pending',
        ]);

        $check = ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'document_review',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/compliance-checks/{$check->id}", [
            'environmental_claim_id' => $claim->id,
            'check_type' => 'document_review',
            'status' => 'failed',
            'result_summary' => 'Non conforme aux normes.',
        ]);

        $response->assertRedirect(route('admin.compliance-checks.show', $check))
            ->assertSessionHas('success', 'Vérification de conformité mise à jour. Une alerte a été créée automatiquement et la déclaration a été rejetée.');

        $this->assertSame('failed', $check->fresh()->status);
        $this->assertSame('rejected', $claim->fresh()->status);
    }

    public function test_admin_can_delete_compliance_check(): void
    {
        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Produit Biologique',
            'status' => 'pending',
        ]);

        $check = ComplianceCheck::create([
            'environmental_claim_id' => $claim->id,
            'product_id' => $product->id,
            'check_type' => 'document_review',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->delete("/admin/compliance-checks/{$check->id}")
            ->assertRedirect(route('admin.compliance-checks.index'))
            ->assertSessionHas('success', 'Vérification de conformité supprimée.');

        $this->assertModelMissing($check);
    }
}
