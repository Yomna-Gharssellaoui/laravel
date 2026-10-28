<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAssistantAndQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_answers_from_product_data_without_an_api_key(): void
    {
        config(['services.anthropic.api_key' => null]);
        $product = Product::factory()->create(['status' => 'published', 'origin' => 'Sfax, Tunisie']);

        $this->actingAs(User::factory()->create())
            ->postJson("/produits/{$product->id}/assistant", ['question' => 'D’où vient ce produit ?'])
            ->assertOk()
            ->assertJson(['ai' => false])
            ->assertJsonPath('answer', fn (string $answer) => str_contains($answer, 'origine Sfax, Tunisie'));
    }

    public function test_assistant_requires_login_and_a_published_product(): void
    {
        $draft = Product::factory()->create(['status' => 'draft']);
        $published = Product::factory()->create(['status' => 'published']);

        $this->postJson("/produits/{$published->id}/assistant", ['question' => 'Bonjour ?'])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson("/produits/{$draft->id}/assistant", ['question' => 'Bonjour ?'])->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson("/produits/{$published->id}/assistant", ['question' => ''])->assertUnprocessable();
    }

    public function test_product_sheet_contains_the_traceability_evidence(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'ingredients' => 'Farine, gluten']);

        $sheet = app(ProductAssistant::class)->productSheet($product);

        $this->assertStringContainsString('Farine, gluten', $sheet);
        $this->assertStringContainsString($product->producer->company_name, $sheet);
    }

    public function test_qr_code_of_a_published_product_can_be_downloaded(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->get("/produits/{$product->id}/qr-code")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->get('/produits/'.Product::factory()->create(['status' => 'draft'])->id.'/qr-code')->assertNotFound();
    }
}
