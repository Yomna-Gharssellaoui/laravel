<?php

namespace Tests\Feature\Admin;

use App\Models\Producer;
use App\Models\Product;
use App\Models\Transformer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_products_by_name_barcode_and_sku(): void
    {
        $prod1 = Product::factory()->create([
            'name' => 'Miel de Lavande',
            'barcode' => '3700000000001',
            'sku' => 'SKU-MIEL-01',
        ]);
        $prod2 = Product::factory()->create([
            'name' => 'Confiture de Fraise',
            'barcode' => '3700000000002',
            'sku' => 'SKU-CONF-02',
        ]);

        // Search by name (Line 25)
        $this->actingAs($this->admin)->get('/admin/products?q=Lavande')
            ->assertOk()
            ->assertSee('Miel de Lavande')
            ->assertDontSee('Confiture de Fraise');

        // Search by barcode (Line 26)
        $this->actingAs($this->admin)->get('/admin/products?q=3700000000001')
            ->assertOk()
            ->assertSee('Miel de Lavande')
            ->assertDontSee('Confiture de Fraise');

        // Search by sku (Line 27)
        $this->actingAs($this->admin)->get('/admin/products?q=SKU-CONF-02')
            ->assertOk()
            ->assertSee('Confiture de Fraise')
            ->assertDontSee('Miel de Lavande');
    }

    public function test_admin_can_delete_a_product_and_its_image(): void
    {
        Storage::fake('public');

        $imagePath = 'products/sample.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');

        $product = Product::factory()->create([
            'image_path' => $imagePath,
        ]);

        $this->actingAs($this->admin)->delete("/admin/products/{$product->id}")
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Produit supprimé (ainsi que son empreinte, ses traces, certifications, avis, alertes et analyses).');

        Storage::disk('public')->assertMissing($imagePath);
        $this->assertModelMissing($product);
    }

    public function test_transformer_updating_product_preserves_locked_producer_and_transformer_ids(): void
    {
        $transformerUser = User::factory()->role('transformer')->create();
        $transformer = Transformer::factory()->create(['user_id' => $transformerUser->id]);
        $producer = Producer::factory()->create();
        $otherProducer = Producer::factory()->create();
        $otherTransformer = Transformer::factory()->create();

        $product = Product::factory()->create([
            'producer_id' => $producer->id,
            'transformer_id' => $transformer->id,
            'status' => 'draft',
            'name' => 'Farine Artisanale',
        ]);

        $response = $this->actingAs($transformerUser)->put("/admin/products/{$product->id}", [
            'name' => 'Farine Artisanale T65',
            'status' => 'draft',
            'producer_id' => $otherProducer->id,
            'transformer_id' => $otherTransformer->id,
        ]);

        $response->assertRedirect(route('admin.products.show', $product))
            ->assertSessionHas('success', 'Produit mis à jour.');

        $fresh = $product->fresh();
        $this->assertSame('Farine Artisanale T65', $fresh->name);
        $this->assertSame($producer->id, $fresh->producer_id);
        $this->assertSame($transformer->id, $fresh->transformer_id);
    }

    public function test_admin_updating_product_with_new_image_deletes_old_image(): void
    {
        Storage::fake('public');

        $oldImagePath = 'products/old_image.jpg';
        Storage::disk('public')->put($oldImagePath, 'old-content');

        $product = Product::factory()->create([
            'image_path' => $oldImagePath,
            'status' => 'draft',
        ]);

        $file = $this->fakeImage('new_product.gif');

        $response = $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'status' => 'draft',
            'image' => $file,
        ]);

        $response->assertRedirect(route('admin.products.show', $product))
            ->assertSessionHas('success', 'Produit mis à jour.');

        Storage::disk('public')->assertMissing($oldImagePath);

        $fresh = $product->fresh();
        $this->assertNotNull($fresh->image_path);
        Storage::disk('public')->assertExists($fresh->image_path);
    }

    public function test_admin_updating_product_with_remove_image_deletes_existing_image(): void
    {
        Storage::fake('public');

        $oldImagePath = 'products/remove_me.jpg';
        Storage::disk('public')->put($oldImagePath, 'image-content');

        $product = Product::factory()->create([
            'image_path' => $oldImagePath,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'status' => 'draft',
            'remove_image' => true,
        ]);

        $response->assertRedirect(route('admin.products.show', $product))
            ->assertSessionHas('success', 'Produit mis à jour.');

        Storage::disk('public')->assertMissing($oldImagePath);

        $this->assertNull($product->fresh()->image_path);
    }

    public function test_admin_can_create_a_product_with_image(): void
    {
        Storage::fake('public');
        $producer = Producer::factory()->create();

        $file = $this->fakeImage('product_cover.gif');

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Pain Bio',
            'producer_id' => $producer->id,
            'status' => 'draft',
            'image' => $file,
        ]);

        $product = Product::where('name', 'Pain Bio')->firstOrFail();

        $response->assertRedirect(route('admin.products.show', $product))
            ->assertSessionHas('success', 'Produit créé avec succès.');

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_producer_ownership_is_enforced_when_creating_product(): void
    {
        $producerUser = User::factory()->role('producer')->create();
        $myProducer = Producer::factory()->create(['user_id' => $producerUser->id]);
        $otherProducer = Producer::factory()->create();

        $this->actingAs($producerUser)->post('/admin/products', [
            'name' => 'Pommes du Verger',
            'producer_id' => $otherProducer->id,
            'status' => 'draft',
        ]);

        $product = Product::where('name', 'Pommes du Verger')->firstOrFail();
        $this->assertSame($myProducer->id, $product->producer_id);
    }

    public function test_cannot_publish_product_without_footprint_and_certification(): void
    {
        $product = Product::factory()->create(['status' => 'draft']);

        $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'status' => 'published',
        ])->assertSessionHasErrors('status');
    }

    private function fakeImage(string $name = 'test.gif'): UploadedFile
    {
        // 1x1 transparent GIF (works without PHP GD extension)
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return UploadedFile::fake()->createWithContent($name, $gif);
    }
}
