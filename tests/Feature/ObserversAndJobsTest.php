<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeClaimJob;
use App\Models\Alert;
use App\Models\ComplianceCheck;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CriticalAlertNotification;
use App\Services\GreenwashingAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class ObserversAndJobsTest extends TestCase
{
    use RefreshDatabase;

    /** Helper: create an EnvironmentalClaim without a factory. */
    private function makeClaim(Product $product, array $attrs = []): EnvironmentalClaim
    {
        return EnvironmentalClaim::create(array_merge([
            'product_id' => $product->id,
            'claim_type' => 'Empreinte carbone',
            'title'      => 'Test claim',
            'status'     => 'pending',
        ], $attrs));
    }

    /** Helper: create a ComplianceCheck without a factory. */
    private function makeCheck(Product $product, array $attrs = []): ComplianceCheck
    {
        return ComplianceCheck::create(array_merge([
            'product_id' => $product->id,
            'check_type' => 'document_review',
            'status'     => 'pending',
        ], $attrs));
    }

    public function test_alert_observer_notifies_admins_on_high_or_critical_alert(): void
    {
        Notification::fake();

        $admin        = User::factory()->role('admin')->create();
        $blockedAdmin = User::factory()->role('admin')->create(['blocked_at' => now()]);

        $product = Product::factory()->create();
        Alert::create([
            'product_id' => $product->id,
            'alert_type' => 'compliance',
            'severity'   => 'critical',
            'title'      => 'Alerte Critique Test',
            'detected_at' => now(),
            'status'     => 'open',
        ]);

        Notification::assertSentTo($admin, CriticalAlertNotification::class);
        Notification::assertNotSentTo($blockedAdmin, CriticalAlertNotification::class);
    }

    public function test_compliance_check_observer_creates_alert_on_failed_check(): void
    {
        $product = Product::factory()->create();
        $claim   = $this->makeClaim($product);

        $check = $this->makeCheck($product, [
            'environmental_claim_id' => $claim->id,
            'status'                 => 'failed',
            'result_summary'         => 'Audit négatif.',
        ]);

        $this->assertDatabaseHas('alerts', [
            'compliance_check_id' => $check->id,
            'product_id'          => $product->id,
            'severity'            => 'high',
        ]);

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
    }

    public function test_compliance_check_observer_verifies_claim_when_all_pass(): void
    {
        $product = Product::factory()->create();
        $claim   = $this->makeClaim($product);

        $this->makeCheck($product, [
            'environmental_claim_id' => $claim->id,
            'status'                 => 'passed',
        ]);

        $claim->refresh();
        $this->assertEquals('verified', $claim->status);
    }

    public function test_analyze_claim_job_delegates_to_analyzer(): void
    {
        $product = Product::factory()->create();
        $claim   = $this->makeClaim($product);

        $mock = Mockery::mock(GreenwashingAnalyzer::class);
        $mock->shouldReceive('analyzeClaim')->once()->with($claim);

        (new AnalyzeClaimJob($claim))->handle($mock);
    }
}
