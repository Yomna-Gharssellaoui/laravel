<?php

namespace Tests\Feature\Admin;

use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_alerts_by_type_severity_status_and_query(): void
    {
        $prod = Product::factory()->create();

        $alert1 = Alert::factory()->create([
            'product_id' => $prod->id,
            'alert_type' => 'greenwashing',
            'severity' => 'critical',
            'status' => 'open',
            'title' => 'Allégation trompeuse sur le cacao',
        ]);

        $alert2 = Alert::factory()->create([
            'product_id' => $prod->id,
            'alert_type' => 'empreinte_élevée',
            'severity' => 'low',
            'status' => 'resolved',
            'title' => 'Émissions CO2 légèrement au-dessus',
            'resolution' => 'Corrigé avec le fournisseur',
        ]);

        // Filter by alert_type
        $this->actingAs($this->admin)->get('/admin/alerts?alert_type=greenwashing')
            ->assertOk()
            ->assertSee('Allégation trompeuse sur le cacao')
            ->assertDontSee('Émissions CO2 légèrement au-dessus');

        // Filter by severity
        $this->actingAs($this->admin)->get('/admin/alerts?severity=critical')
            ->assertOk()
            ->assertSee('Allégation trompeuse sur le cacao')
            ->assertDontSee('Émissions CO2 légèrement au-dessus');

        // Filter by status
        $this->actingAs($this->admin)->get('/admin/alerts?status=resolved')
            ->assertOk()
            ->assertSee('Émissions CO2 légèrement au-dessus')
            ->assertDontSee('Allégation trompeuse sur le cacao');

        // Search by query
        $this->actingAs($this->admin)->get('/admin/alerts?q=cacao')
            ->assertOk()
            ->assertSee('Allégation trompeuse sur le cacao')
            ->assertDontSee('Émissions CO2 légèrement au-dessus');
    }

    public function test_admin_can_view_create_show_edit_screens(): void
    {
        $alert = Alert::factory()->create();

        $this->actingAs($this->admin)->get('/admin/alerts/create')
            ->assertOk();

        $this->actingAs($this->admin)->get("/admin/alerts/{$alert->id}")
            ->assertOk()
            ->assertSee($alert->title);

        $this->actingAs($this->admin)->get("/admin/alerts/{$alert->id}/edit")
            ->assertOk()
            ->assertSee($alert->title);
    }

    public function test_admin_can_create_an_alert(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/alerts', [
            'product_id' => $product->id,
            'alert_type' => 'greenwashing',
            'severity' => 'high',
            'title' => 'Termes écologiques non justifiés',
            'description' => 'Le terme 100% vert est employé sans certification.',
            'status' => 'open',
        ]);

        $alert = Alert::where('title', 'Termes écologiques non justifiés')->firstOrFail();

        $response->assertRedirect(route('admin.alerts.show', $alert))
            ->assertSessionHas('success', 'Alerte créée.');

        $this->assertSame('high', $alert->severity);
        $this->assertSame('open', $alert->status);
    }

    public function test_resolution_is_required_when_status_is_resolved_or_dismissed(): void
    {
        $product = Product::factory()->create();

        // Missing resolution when resolved
        $this->actingAs($this->admin)->post('/admin/alerts', [
            'product_id' => $product->id,
            'alert_type' => 'greenwashing',
            'severity' => 'high',
            'title' => 'Test résolution manquante',
            'status' => 'resolved',
            'resolution' => '',
        ])->assertSessionHasErrors('resolution');

        // Missing resolution when dismissed
        $this->actingAs($this->admin)->post('/admin/alerts', [
            'product_id' => $product->id,
            'alert_type' => 'greenwashing',
            'severity' => 'high',
            'title' => 'Test rejet sans explication',
            'status' => 'dismissed',
            'resolution' => '',
        ])->assertSessionHasErrors('resolution');
    }

    public function test_admin_can_update_an_alert_with_resolution(): void
    {
        $alert = Alert::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->admin)->put("/admin/alerts/{$alert->id}", [
            'product_id' => $alert->product_id,
            'alert_type' => $alert->alert_type,
            'severity' => 'medium',
            'title' => $alert->title,
            'status' => 'resolved',
            'resolution' => 'Le packaging a été réimprimé sans la mention abusive.',
        ]);

        $response->assertRedirect(route('admin.alerts.show', $alert))
            ->assertSessionHas('success', 'Alerte mise à jour.');

        $fresh = $alert->fresh();
        $this->assertSame('resolved', $fresh->status);
        $this->assertSame('medium', $fresh->severity);
        $this->assertSame('Le packaging a été réimprimé sans la mention abusive.', $fresh->resolution);
    }

    public function test_admin_can_delete_an_alert(): void
    {
        $alert = Alert::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/alerts/{$alert->id}")
            ->assertRedirect(route('admin.alerts.index'))
            ->assertSessionHas('success', 'Alerte supprimée.');

        $this->assertModelMissing($alert);
    }
}
