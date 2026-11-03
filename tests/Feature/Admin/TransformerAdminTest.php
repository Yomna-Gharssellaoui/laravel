<?php

namespace Tests\Feature\Admin;

use App\Models\Transformer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransformerAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->role('admin')->create();
    }

    public function test_admin_can_list_transformers_and_search(): void
    {
        $t1 = Transformer::factory()->create(['company_name' => 'Moulin Huile Bio', 'location' => 'Sfax']);
        $t2 = Transformer::factory()->create(['company_name' => 'Conserverie Moderne', 'location' => 'Bizerte']);

        $response = $this->actingAs($this->admin)->get(route('admin.transformers.index', ['q' => 'Moulin']));

        $response->assertOk()
            ->assertSee('Moulin Huile Bio')
            ->assertDontSee('Conserverie Moderne');
    }

    public function test_admin_can_view_create_page(): void
    {
        $user = User::factory()->role('transformer')->create(['name' => 'Meunier Pro']);

        $response = $this->actingAs($this->admin)->get(route('admin.transformers.create'));

        $response->assertOk()
            ->assertSee('Meunier Pro');
    }

    public function test_admin_can_store_transformer(): void
    {
        $user = User::factory()->role('transformer')->create();

        $response = $this->actingAs($this->admin)->post(route('admin.transformers.store'), [
            'user_id' => $user->id,
            'company_name' => 'Huilerie du Nord',
            'location' => 'Zaghouan',
            'latitude' => 36.4,
            'longitude' => 10.14,
            'transformation_type' => 'Pression à froid',
            'process_description' => 'Extraction première pression mécanique.',
            'production_capacity' => '20000 L/an',
            'certifications' => 'ISO 22000, Bio',
        ]);

        $transformer = Transformer::where('company_name', 'Huilerie du Nord')->first();
        $this->assertNotNull($transformer);
        $this->assertEquals($user->id, $transformer->user_id);
        $this->assertContains('ISO 22000', $transformer->certifications);

        $response->assertRedirect(route('admin.transformers.show', $transformer));
    }

    public function test_admin_can_show_transformer(): void
    {
        $transformer = Transformer::factory()->create(['company_name' => 'Atelier Artisanal']);

        $response = $this->actingAs($this->admin)->get(route('admin.transformers.show', $transformer));

        $response->assertOk()
            ->assertSee('Atelier Artisanal');
    }

    public function test_admin_can_edit_transformer(): void
    {
        $transformer = Transformer::factory()->create(['company_name' => 'Usine Delta']);

        $response = $this->actingAs($this->admin)->get(route('admin.transformers.edit', $transformer));

        $response->assertOk()
            ->assertSee('Usine Delta');
    }

    public function test_admin_can_update_transformer(): void
    {
        $transformer = Transformer::factory()->create(['company_name' => 'Ancien Transformer']);

        $response = $this->actingAs($this->admin)->put(route('admin.transformers.update', $transformer), [
            'company_name' => 'Transformateur Bio Rénové',
            'location' => 'Monastir',
            'transformation_type' => 'Séchage solaire',
        ]);

        $response->assertRedirect(route('admin.transformers.show', $transformer));
        $this->assertDatabaseHas('transformers', [
            'id' => $transformer->id,
            'company_name' => 'Transformateur Bio Rénové',
            'transformation_type' => 'Séchage solaire',
        ]);
    }

    public function test_admin_can_delete_transformer(): void
    {
        $transformer = Transformer::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.transformers.destroy', $transformer));

        $response->assertRedirect(route('admin.transformers.index'));
        $this->assertDatabaseMissing('transformers', ['id' => $transformer->id]);
    }
}
