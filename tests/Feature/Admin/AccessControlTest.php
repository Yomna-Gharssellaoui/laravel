<?php

namespace Tests\Feature\Admin;

use App\Models\Producer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_consumer_cannot_open_the_back_office(): void
    {
        $consumer = User::factory()->create();

        $this->actingAs($consumer)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($consumer)->get('/admin/products')->assertForbidden();
        $this->actingAs($consumer)->delete('/admin/products/1')->assertForbidden();
    }

    public function test_unverified_admin_must_verify_email_first(): void
    {
        $admin = User::factory()->admin()->unverified()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_admin_can_open_every_module(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            'dashboard', 'users', 'products', 'environmental-footprints', 'environmental-claims',
            'compliance-checks', 'producers', 'transformers', 'distributors', 'supply-chain-traces',
            'certificates', 'reviews', 'alerts', 'ai-analyses', 'consumers', 'personal-ratings',
        ] as $module) {
            $this->actingAs($admin)->get("/admin/$module")->assertOk();
        }
    }

    public function test_dashboard_redirects_according_to_role(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect(route('consumer.profile'));
    }

    public function test_producer_only_sees_and_edits_own_products(): void
    {
        $user = User::factory()->role('producer')->create();
        $own = Product::factory()->for(Producer::factory()->create(['user_id' => $user->id]))->create(['name' => 'Mon miel']);
        $other = Product::factory()->create(['name' => 'Produit concurrent']);

        $this->actingAs($user)->get('/admin/products')
            ->assertOk()
            ->assertSee('Mon miel')
            ->assertDontSee('Produit concurrent');

        $this->actingAs($user)->get("/admin/products/{$own->id}/edit")->assertOk();
        $this->actingAs($user)->get("/admin/products/{$other->id}")->assertForbidden();
        $this->actingAs($user)->put("/admin/products/{$other->id}", ['name' => 'x', 'status' => 'draft'])->assertForbidden();
        $this->actingAs($user)->delete("/admin/products/{$other->id}")->assertForbidden();
    }

    public function test_producer_cannot_reach_admin_only_modules(): void
    {
        $user = User::factory()->role('producer')->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/alerts')->assertForbidden();
        $this->actingAs($user)->get('/admin/ai-analyses')->assertForbidden();
    }

    public function test_product_created_by_a_producer_is_always_attached_to_them(): void
    {
        $user = User::factory()->role('producer')->create();
        $producer = Producer::factory()->create(['user_id' => $user->id]);
        $otherProducer = Producer::factory()->create();

        $this->actingAs($user)->post('/admin/products', [
            'name' => 'Huile d’olive',
            'status' => 'draft',
            'producer_id' => $otherProducer->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Huile d’olive', 'producer_id' => $producer->id]);
    }

    public function test_blocked_user_is_logged_out(): void
    {
        $user = User::factory()->admin()->blocked()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_blocked_user_cannot_log_in(): void
    {
        $user = User::factory()->blocked()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
