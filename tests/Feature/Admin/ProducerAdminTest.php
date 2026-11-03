<?php

namespace Tests\Feature\Admin;

use App\Models\Producer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProducerAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->role('admin')->create();
    }

    public function test_admin_can_list_producers_and_search(): void
    {
        $p1 = Producer::factory()->create(['company_name' => 'Ferme Bio Alpha', 'location' => 'Tunis']);
        $p2 = Producer::factory()->create(['company_name' => 'Verger Beta', 'location' => 'Sfax']);

        $response = $this->actingAs($this->admin)->get(route('admin.producers.index', ['q' => 'Alpha']));

        $response->assertOk()
            ->assertSee('Ferme Bio Alpha')
            ->assertDontSee('Verger Beta');
    }

    public function test_admin_can_view_create_page(): void
    {
        $unlinkedUser = User::factory()->role('producer')->create(['name' => 'Agriculteur Libre']);

        $response = $this->actingAs($this->admin)->get(route('admin.producers.create'));

        $response->assertOk()
            ->assertSee('Agriculteur Libre');
    }

    public function test_admin_can_store_producer(): void
    {
        $user = User::factory()->role('producer')->create();

        $response = $this->actingAs($this->admin)->post(route('admin.producers.store'), [
            'user_id' => $user->id,
            'company_name' => 'Domaine du Soleil',
            'location' => 'Béja, Tunisie',
            'latitude' => 36.72,
            'longitude' => 9.18,
            'farming_method' => 'Biologique',
            'crop_types' => 'Blé, Olivier, Tomate',
            'production_capacity' => '50 tonnes/an',
            'certifications' => 'Bio Tunisie, Ecocert',
        ]);

        $producer = Producer::where('company_name', 'Domaine du Soleil')->first();
        $this->assertNotNull($producer);
        $this->assertEquals($user->id, $producer->user_id);
        $this->assertIsArray($producer->crop_types);
        $this->assertContains('Blé', $producer->crop_types);
        $this->assertContains('Ecocert', $producer->certifications);

        $response->assertRedirect(route('admin.producers.show', $producer));
    }

    public function test_admin_can_show_producer(): void
    {
        $producer = Producer::factory()->create(['company_name' => 'Ferme du Cap']);

        $response = $this->actingAs($this->admin)->get(route('admin.producers.show', $producer));

        $response->assertOk()
            ->assertSee('Ferme du Cap');
    }

    public function test_admin_can_edit_producer(): void
    {
        $producer = Producer::factory()->create(['company_name' => 'Ferme du Sud']);

        $response = $this->actingAs($this->admin)->get(route('admin.producers.edit', $producer));

        $response->assertOk()
            ->assertSee('Ferme du Sud');
    }

    public function test_admin_can_update_producer(): void
    {
        $producer = Producer::factory()->create(['company_name' => 'Ancien Nom']);

        $response = $this->actingAs($this->admin)->put(route('admin.producers.update', $producer), [
            'company_name' => 'Nouveau Nom Bio',
            'location' => 'Nabeul',
            'crop_types' => 'Agrumes, Fraises',
        ]);

        $response->assertRedirect(route('admin.producers.show', $producer));
        $this->assertDatabaseHas('producers', [
            'id' => $producer->id,
            'company_name' => 'Nouveau Nom Bio',
            'location' => 'Nabeul',
        ]);
    }

    public function test_admin_can_delete_producer(): void
    {
        $producer = Producer::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.producers.destroy', $producer));

        $response->assertRedirect(route('admin.producers.index'));
        $this->assertDatabaseMissing('producers', ['id' => $producer->id]);
    }
}
