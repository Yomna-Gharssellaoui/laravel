<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_ml_models_dashboard(): void
    {
        $admin = User::factory()->role('admin')->create();

        $response = $this->actingAs($admin)->get(route('admin.ml-models.index'));

        $response->assertOk()
            ->assertSee('Modèles de machine learning');
    }

    public function test_non_admin_cannot_view_ml_models_dashboard(): void
    {
        $consumer = User::factory()->role('consumer')->create();

        $response = $this->actingAs($consumer)->get(route('admin.ml-models.index'));

        $response->assertForbidden();
    }
}
