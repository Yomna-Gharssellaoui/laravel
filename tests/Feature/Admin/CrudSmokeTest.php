<?php

namespace Tests\Feature\Admin;

use App\Models;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeds the demo data and opens every back-office page, to catch broken views.
 */
class CrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const MODULES = [
        'users' => [User::class, true],
        'products' => [Models\Product::class, true],
        'environmental-footprints' => [Models\EnvironmentalFootprint::class, true],
        'environmental-claims' => [Models\EnvironmentalClaim::class, true],
        'compliance-checks' => [Models\ComplianceCheck::class, true],
        'producers' => [Models\Producer::class, true],
        'transformers' => [Models\Transformer::class, true],
        'distributors' => [Models\Distributor::class, true],
        'supply-chain-traces' => [Models\SupplyChainTrace::class, true],
        'certificates' => [Models\Certificate::class, true],
        'alerts' => [Models\Alert::class, true],
        'consumers' => [Models\Consumer::class, true],
        'reviews' => [Models\Review::class, false],
        'ai-analyses' => [Models\AIAnalysis::class, false],
        'personal-ratings' => [Models\PersonalRating::class, false],
    ];

    public function test_every_admin_page_renders_with_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@traceverte.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        foreach (self::MODULES as $module => [$model, $editable]) {
            $this->actingAs($admin)->get("/admin/$module")->assertOk();

            $record = $model::query()->first();
            if ($record) {
                $this->actingAs($admin)->get("/admin/$module/{$record->getKey()}")->assertOk();
            }

            if ($editable) {
                $this->actingAs($admin)->get("/admin/$module/create")->assertOk();
                if ($record) {
                    $this->actingAs($admin)->get("/admin/$module/{$record->getKey()}/edit")->assertOk();
                }
            }
        }
    }

    public function test_empty_lists_render(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (array_keys(self::MODULES) as $module) {
            $this->actingAs($admin)->get("/admin/$module")->assertOk();
        }
    }

    public function test_demo_producer_sees_their_back_office(): void
    {
        $this->seed(DatabaseSeeder::class);
        $producer = User::where('email', 'producer@traceverte.test')->firstOrFail();

        $this->actingAs($producer)->get('/admin/dashboard')->assertOk();
        $this->actingAs($producer)->get('/admin/products')->assertOk();
        $this->actingAs($producer)->get('/admin/products/create')->assertOk();
        $this->actingAs($producer)->get('/admin/certificates')->assertOk();
        $this->actingAs($producer)->get('/admin/supply-chain-traces')->assertOk();
    }

    public function test_public_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Models\Product::where('status', 'published')->firstOrFail();
        $consumer = User::where('email', 'consumer@traceverte.test')->firstOrFail();

        $this->get('/')->assertOk();
        $this->get('/produits')->assertOk();
        $this->get("/produits/{$product->id}")->assertOk();
        $this->get("/produits/{$product->id}/trace")->assertOk();
        $this->actingAs($consumer)->get("/produits/{$product->id}")->assertOk()->assertSee('Modifier mon avis');
        $this->actingAs($consumer)->get('/mon-profil')->assertOk();
        $this->actingAs($consumer)->get('/mes-recommandations')->assertOk();
    }
}
