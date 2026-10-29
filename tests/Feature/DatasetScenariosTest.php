<?php

namespace Tests\Feature;

use App\Models\AIAnalysis;
use App\Models\Alert;
use App\Models\Certificate;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checks that the demo dataset supports the scenarios described in docs/GUIDE-DE-TEST.md.
 */
class DatasetScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_public_catalogue_only_lists_published_products(): void
    {
        $this->assertSame(7, Product::where('status', 'published')->count());

        $this->get('/produits')->assertOk()->assertDontSee('Tomates séchées')->assertDontSee('Amandes de Sfax');
        $this->get('/produits/'.Product::where('sku', 'TV-TOM-008')->value('id'))->assertNotFound();
    }

    public function test_consumer_cannot_reach_the_back_office(): void
    {
        $consumer = $this->user('consumer@traceverte.test');

        $this->actingAs($consumer)->get('/dashboard')->assertRedirect(route('consumer.profile'));
        $this->actingAs($consumer)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($consumer)->get('/admin/users')->assertForbidden();
    }

    public function test_special_accounts(): void
    {
        $this->actingAs($this->user('nouveau@traceverte.test'))->get('/mon-profil')->assertRedirect(route('verification.notice'));
        auth()->logout();

        $this->post('/login', ['email' => 'bloque@traceverte.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_business_accounts_only_see_their_own_data(): void
    {
        $dattes = Product::where('sku', 'TV-DAT-002')->firstOrFail();

        $this->actingAs($this->user('producer@traceverte.test'))->get('/admin/products')
            ->assertSee('Huile d’olive extra vierge bio')->assertSee('Amandes de Sfax')->assertDontSee('Dattes Deglet Nour');
        $this->actingAs($this->user('producer@traceverte.test'))->get("/admin/products/{$dattes->id}")->assertForbidden();
        $this->actingAs($this->user('producer2@traceverte.test'))->get("/admin/products/{$dattes->id}/edit")->assertOk();

        $this->actingAs($this->user('distributor@traceverte.test'))->get('/admin/supply-chain-traces')
            ->assertSee('Couscous complet')->assertDontSee('Harissa artisanale');
    }

    public function test_dattes_cannot_be_republished_from_draft_and_tomatoes_cannot_be_published(): void
    {
        $tomates = Product::where('sku', 'TV-TOM-008')->firstOrFail();

        $this->actingAs($this->user('admin@traceverte.test'))
            ->put("/admin/products/{$tomates->id}", ['name' => $tomates->name, 'status' => 'published'])
            ->assertSessionHasErrors('status');
    }

    public function test_sara_never_gets_the_gluten_product(): void
    {
        $this->actingAs($this->user('sara@traceverte.test'))->get('/mes-recommandations')
            ->assertOk()
            ->assertSeeInOrder(['Produits écartés', 'Couscous complet']);
    }

    public function test_scan_command_raises_the_four_expected_alerts_once(): void
    {
        $this->assertSame(1, Alert::count());

        $this->artisan('traceverte:scan-alerts')->assertSuccessful();
        $this->assertSame(5, Alert::count());

        $this->artisan('traceverte:scan-alerts')->assertSuccessful();
        $this->assertSame(5, Alert::count(), 'No duplicate alerts on a second run');

        $this->assertSame('expired', Certificate::where('certificate_number', 'CQ-LR-1209')->value('status'));
    }

    public function test_moderation_queue_and_greenwashing_scores(): void
    {
        $this->actingAs($this->user('admin@traceverte.test'))->get('/admin/reviews?status=pending')
            ->assertSee('La meilleure harissa')->assertSee('Correct');

        $this->assertSame(100, AIAnalysis::whereHas('environmentalClaim', fn ($q) => $q->where('title', 'like', 'Leben%'))->value('greenwashing_score'));
    }
}
