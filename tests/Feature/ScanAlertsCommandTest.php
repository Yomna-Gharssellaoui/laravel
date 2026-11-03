<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Certificate;
use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use App\Models\SupplyChainTrace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_alerts_detects_expired_and_expiring_certificates(): void
    {
        $product = Product::factory()->create();

        // Expired certificate
        Certificate::factory()->create([
            'product_id' => $product->id,
            'status' => 'verified',
            'expiry_date' => now()->subDays(5),
            'certificate_type' => 'Bio Ecocert',
            'certificate_number' => 'CERT-1234',
        ]);

        // Expiring soon certificate
        Certificate::factory()->create([
            'product_id' => $product->id,
            'status' => 'verified',
            'expiry_date' => now()->addDays(10),
            'certificate_type' => 'ISO 14001',
            'certificate_number' => 'CERT-5678',
        ]);

        $this->artisan('traceverte:scan-alerts')
            ->assertSuccessful();

        $this->assertDatabaseHas('alerts', [
            'product_id' => $product->id,
            'alert_type' => 'certificate_expired',
        ]);

        $this->assertDatabaseHas('alerts', [
            'product_id' => $product->id,
            'alert_type' => 'certificate_expiring',
        ]);
    }

    public function test_scan_alerts_detects_delayed_traces_and_high_footprints(): void
    {
        $product = Product::factory()->create();

        SupplyChainTrace::factory()->create([
            'product_id' => $product->id,
            'status' => 'delayed',
            'current_stage' => 'Port de Radès',
        ]);

        EnvironmentalFootprint::factory()->create([
            'product_id' => $product->id,
            'co2_emissions' => 25.5, // > 15
        ]);

        $this->artisan('traceverte:scan-alerts')
            ->assertSuccessful();

        $this->assertDatabaseHas('alerts', [
            'product_id' => $product->id,
            'alert_type' => 'logistics_delay',
        ]);

        $this->assertDatabaseHas('alerts', [
            'product_id' => $product->id,
            'alert_type' => 'high_footprint',
        ]);
    }
}
