<?php

namespace Tests\Feature\Admin;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\SupplyChainTrace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyChainTraceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_traces_by_product_name_query(): void
    {
        $productA = Product::factory()->create(['name' => 'Pommes Gala']);
        $productB = Product::factory()->create(['name' => 'Jus de Poire']);

        SupplyChainTrace::factory()->create(['product_id' => $productA->id]);
        SupplyChainTrace::factory()->create(['product_id' => $productB->id]);

        $this->actingAs($this->admin)->get('/admin/supply-chain-traces?q=Gala')
            ->assertOk()
            ->assertSee('Pommes Gala')
            ->assertDontSee('Jus de Poire');
    }

    public function test_admin_can_filter_traces_by_status(): void
    {
        $product = Product::factory()->create();

        $traceTransit = SupplyChainTrace::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_transit',
        ]);
        $traceDelivered = SupplyChainTrace::factory()->create([
            'product_id' => $product->id,
            'status' => 'delivered',
        ]);

        $this->actingAs($this->admin)->get('/admin/supply-chain-traces?status=delivered')
            ->assertOk()
            ->assertSee($traceDelivered->current_stage);
    }

    public function test_admin_can_view_create_and_edit_screens(): void
    {
        $trace = SupplyChainTrace::factory()->create();

        $this->actingAs($this->admin)->get('/admin/supply-chain-traces/create')
            ->assertOk();

        $this->actingAs($this->admin)->get("/admin/supply-chain-traces/{$trace->id}")
            ->assertOk()
            ->assertSee($trace->product->name);

        $this->actingAs($this->admin)->get("/admin/supply-chain-traces/{$trace->id}/edit")
            ->assertOk()
            ->assertSee($trace->product->name);
    }

    public function test_distributor_sees_only_own_distributor_in_form_data(): void
    {
        $distributorUser = User::factory()->role('distributor')->create();
        $distributor = Distributor::factory()->create([
            'user_id' => $distributorUser->id,
            'company_name' => 'Logistique Express 75',
        ]);
        $otherDistributor = Distributor::factory()->create([
            'company_name' => 'Autre Transporteur 99',
        ]);

        $product = Product::factory()->create();
        $trace = SupplyChainTrace::factory()->create([
            'product_id' => $product->id,
            'distributor_id' => $distributor->id,
        ]);

        // Create view (Line 112)
        $this->actingAs($distributorUser)->get('/admin/supply-chain-traces/create')
            ->assertOk()
            ->assertSee('Logistique Express 75')
            ->assertDontSee('Autre Transporteur 99');

        // Edit view (Line 112)
        $this->actingAs($distributorUser)->get("/admin/supply-chain-traces/{$trace->id}/edit")
            ->assertOk()
            ->assertSee('Logistique Express 75')
            ->assertDontSee('Autre Transporteur 99');
    }

    public function test_admin_can_create_a_supply_chain_trace(): void
    {
        $product = Product::factory()->create();
        $distributor = Distributor::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/supply-chain-traces', [
            'product_id' => $product->id,
            'distributor_id' => $distributor->id,
            'status' => 'in_transit',
            'steps' => [
                [
                    'stage' => 'collecte',
                    'location' => 'Lyon',
                    'at' => '2026-11-01T08:00:00',
                    'lat' => 45.7640,
                    'lon' => 4.8357,
                ],
                [
                    'stage' => 'distribution',
                    'location' => 'Paris',
                    'at' => '2026-11-02T12:00:00',
                    'lat' => 48.8566,
                    'lon' => 2.3522,
                ],
            ],
        ]);

        $trace = SupplyChainTrace::where('product_id', $product->id)->latest()->firstOrFail();

        $response->assertRedirect(route('admin.supply-chain-traces.show', $trace))
            ->assertSessionHas('success', 'Trace logistique créée.');

        $this->assertSame('distribution', $trace->current_stage);
        $this->assertEquals(48.8566, $trace->current_location_lat);
        $this->assertEquals(2.3522, $trace->current_location_lon);
        $this->assertCount(2, $trace->path_history);
        $this->assertGreaterThan(0, $trace->total_distance_km);
    }

    public function test_distributor_forces_their_own_distributor_id(): void
    {
        $distributorUser = User::factory()->role('distributor')->create();
        $myDistributor = Distributor::factory()->create(['user_id' => $distributorUser->id]);

        $product = Product::factory()->create();

        // Line 140: $user->hasRole('distributor') sets $data['distributor_id'] = $user->distributor->id
        $response = $this->actingAs($distributorUser)->post('/admin/supply-chain-traces', [
            'product_id' => $product->id,
            'status' => 'in_transit',
            'steps' => [
                [
                    'stage' => 'transit',
                    'location' => 'Marseille',
                ],
            ],
        ]);

        $trace = SupplyChainTrace::where('product_id', $product->id)->latest()->firstOrFail();

        $response->assertRedirect(route('admin.supply-chain-traces.show', $trace))
            ->assertSessionHas('success', 'Trace logistique créée.');

        $this->assertSame($myDistributor->id, $trace->distributor_id);
    }

    public function test_admin_can_update_a_supply_chain_trace(): void
    {
        $product = Product::factory()->create();
        $distributor = Distributor::factory()->create();

        $trace = SupplyChainTrace::factory()->create([
            'product_id' => $product->id,
            'distributor_id' => $distributor->id,
            'status' => 'in_transit',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/supply-chain-traces/{$trace->id}", [
            'product_id' => $product->id,
            'distributor_id' => $distributor->id,
            'status' => 'delivered',
            'steps' => [
                [
                    'stage' => 'livraison_finale',
                    'location' => 'Lille',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.supply-chain-traces.show', $trace))
            ->assertSessionHas('success', 'Trace logistique mise à jour.');

        $fresh = $trace->fresh();
        $this->assertSame('delivered', $fresh->status);
        $this->assertSame('livraison_finale', $fresh->current_stage);
    }

    public function test_admin_can_delete_a_supply_chain_trace(): void
    {
        $trace = SupplyChainTrace::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/supply-chain-traces/{$trace->id}")
            ->assertRedirect(route('admin.supply-chain-traces.index'))
            ->assertSessionHas('success', 'Trace logistique supprimée.');

        $this->assertModelMissing($trace);
    }
}
