<?php

namespace Tests\Feature\Admin;

use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentalFootprintTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_footprints_by_product_name_query(): void
    {
        $productA = Product::factory()->create(['name' => 'Chocolat Noir']);
        $productB = Product::factory()->create(['name' => 'Farine Complète']);

        EnvironmentalFootprint::factory()->for($productA)->create();
        EnvironmentalFootprint::factory()->for($productB)->create();

        $this->actingAs($this->admin)->get('/admin/environmental-footprints?q=Chocolat')
            ->assertOk()
            ->assertSee('Chocolat Noir')
            ->assertDontSee('Farine Complète');
    }

    public function test_admin_can_view_create_and_edit_screens(): void
    {
        $product = Product::factory()->create(['name' => 'Huile d’Olive']);
        $footprint = EnvironmentalFootprint::factory()->for($product)->create();

        $this->actingAs($this->admin)->get('/admin/environmental-footprints/create')
            ->assertOk();

        $this->actingAs($this->admin)->get("/admin/environmental-footprints/{$footprint->id}")
            ->assertOk()
            ->assertSee('Huile d’Olive');

        $this->actingAs($this->admin)->get("/admin/environmental-footprints/{$footprint->id}/edit")
            ->assertOk()
            ->assertSee('Huile d’Olive');
    }

    public function test_admin_can_create_an_environmental_footprint(): void
    {
        $product = Product::factory()->create(['name' => 'Café Équitable']);

        $response = $this->actingAs($this->admin)->post('/admin/environmental-footprints', [
            'product_id' => $product->id,
            'co2_emissions' => 2.5,
            'water_usage' => 150.0,
            'land_usage' => 0.8,
        ]);

        $footprint = EnvironmentalFootprint::where('product_id', $product->id)->firstOrFail();

        $response->assertRedirect(route('admin.environmental-footprints.show', $footprint))
            ->assertSessionHas('success', 'Empreinte environnementale créée.');

        $this->assertEquals(2.5, $footprint->co2_emissions);
        $this->assertEquals(150.0, $footprint->water_usage);
        $this->assertEquals(0.8, $footprint->land_usage);
    }

    public function test_admin_create_validates_product_id_is_required_and_unique(): void
    {
        $product = Product::factory()->create();
        EnvironmentalFootprint::factory()->for($product)->create();

        $this->actingAs($this->admin)->post('/admin/environmental-footprints', [
            'product_id' => $product->id,
            'co2_emissions' => 1.0,
        ])->assertSessionHasErrors('product_id');

        $this->actingAs($this->admin)->post('/admin/environmental-footprints', [
            'product_id' => 999999,
            'co2_emissions' => 1.0,
        ])->assertSessionHasErrors('product_id');
    }

    public function test_admin_can_update_an_environmental_footprint(): void
    {
        $product = Product::factory()->create(['name' => 'Thé Vert']);
        $footprint = EnvironmentalFootprint::factory()->for($product)->create([
            'co2_emissions' => 1.2,
            'water_usage' => 50.0,
            'land_usage' => 0.5,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/environmental-footprints/{$footprint->id}", [
            'product_id' => $product->id,
            'co2_emissions' => 3.8,
            'water_usage' => 120.5,
            'land_usage' => 1.1,
        ]);

        $response->assertRedirect(route('admin.environmental-footprints.show', $footprint))
            ->assertSessionHas('success', 'Empreinte mise à jour.');

        $fresh = $footprint->fresh();
        $this->assertEquals(3.8, $fresh->co2_emissions);
        $this->assertEquals(120.5, $fresh->water_usage);
        $this->assertEquals(1.1, $fresh->land_usage);
    }

    public function test_admin_can_delete_an_environmental_footprint(): void
    {
        $product = Product::factory()->create();
        $footprint = EnvironmentalFootprint::factory()->for($product)->create();

        $this->actingAs($this->admin)->delete("/admin/environmental-footprints/{$footprint->id}")
            ->assertRedirect(route('admin.environmental-footprints.index'))
            ->assertSessionHas('success', 'Empreinte supprimée.');

        $this->assertModelMissing($footprint);
    }
}
