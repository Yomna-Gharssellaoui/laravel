<?php

namespace Tests\Feature\Admin;

use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->role('admin')->create();
    }

    public function test_admin_can_list_distributors_and_search(): void
    {
        $d1 = Distributor::factory()->create(['company_name' => 'Logistique Express Verte', 'location' => 'Tunis']);
        $d2 = Distributor::factory()->create(['company_name' => 'Transport Global', 'location' => 'Gabès']);

        $response = $this->actingAs($this->admin)->get(route('admin.distributors.index', ['q' => 'Express']));

        $response->assertOk()
            ->assertSee('Logistique Express Verte')
            ->assertDontSee('Transport Global');
    }

    public function test_admin_can_view_create_page(): void
    {
        $user = User::factory()->role('distributor')->create(['name' => 'Chauffeur Pro']);

        $response = $this->actingAs($this->admin)->get(route('admin.distributors.create'));

        $response->assertOk()
            ->assertSee('Chauffeur Pro');
    }

    public function test_admin_can_store_distributor(): void
    {
        $user = User::factory()->role('distributor')->create();

        $response = $this->actingAs($this->admin)->post(route('admin.distributors.store'), [
            'user_id' => $user->id,
            'company_name' => 'Eco Fret Distribution',
            'distributor_type' => 'Grossiste bio',
            'location' => 'Ben Arous',
            'latitude' => 36.75,
            'longitude' => 10.22,
            'coverage_area' => 'Grand Tunis et Sahel',
        ]);

        $distributor = Distributor::where('company_name', 'Eco Fret Distribution')->first();
        $this->assertNotNull($distributor);
        $this->assertEquals($user->id, $distributor->user_id);
        $this->assertEquals('Grossiste bio', $distributor->distributor_type);

        $response->assertRedirect(route('admin.distributors.show', $distributor));
    }

    public function test_admin_can_show_distributor(): void
    {
        $distributor = Distributor::factory()->create(['company_name' => 'Hub Central']);

        $response = $this->actingAs($this->admin)->get(route('admin.distributors.show', $distributor));

        $response->assertOk()
            ->assertSee('Hub Central');
    }

    public function test_admin_can_edit_distributor(): void
    {
        $distributor = Distributor::factory()->create(['company_name' => 'Hub Sud']);

        $response = $this->actingAs($this->admin)->get(route('admin.distributors.edit', $distributor));

        $response->assertOk()
            ->assertSee('Hub Sud');
    }

    public function test_admin_can_update_distributor(): void
    {
        $distributor = Distributor::factory()->create(['company_name' => 'Ancien Distributeur']);

        $response = $this->actingAs($this->admin)->put(route('admin.distributors.update', $distributor), [
            'company_name' => 'Distributeur Moderne',
            'distributor_type' => 'Réseau frais',
            'location' => 'Kairouan',
        ]);

        $response->assertRedirect(route('admin.distributors.show', $distributor));
        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'company_name' => 'Distributeur Moderne',
            'distributor_type' => 'Réseau frais',
        ]);
    }

    public function test_admin_can_delete_distributor(): void
    {
        $distributor = Distributor::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.distributors.destroy', $distributor));

        $response->assertRedirect(route('admin.distributors.index'));
        $this->assertDatabaseMissing('distributors', ['id' => $distributor->id]);
    }
}
