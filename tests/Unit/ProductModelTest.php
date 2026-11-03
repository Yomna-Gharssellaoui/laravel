<?php

namespace Tests\Unit;

use App\Models\Certificate;
use App\Models\EnvironmentalFootprint;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Transformer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_for_publication_checks_footprint_and_certification(): void
    {
        $product = Product::factory()->create();

        // Initially has neither footprint nor certificate
        $missing = $product->missingForPublication();
        $this->assertContains('une empreinte environnementale', $missing);
        $this->assertContains('au moins une certification', $missing);

        // Add footprint
        EnvironmentalFootprint::factory()->for($product)->create();
        $missingAfterFootprint = $product->fresh()->missingForPublication();
        $this->assertNotContains('une empreinte environnementale', $missingAfterFootprint);
        $this->assertContains('au moins une certification', $missingAfterFootprint);

        // Add verified certificate
        Certificate::factory()->for($product)->create(['status' => 'verified']);
        $missingComplete = $product->fresh()->missingForPublication();
        $this->assertEmpty($missingComplete);
    }

    public function test_is_owned_by_checks_roles_and_matching_ids(): void
    {
        $admin = User::factory()->admin()->create();

        $producerUser = User::factory()->role('producer')->create();
        $producer = Producer::factory()->create(['user_id' => $producerUser->id]);

        $transformerUser = User::factory()->role('transformer')->create();
        $transformer = Transformer::factory()->create(['user_id' => $transformerUser->id]);

        $consumer = User::factory()->create(['role' => 'consumer']);

        $product = Product::factory()->create([
            'producer_id' => $producer->id,
            'transformer_id' => $transformer->id,
        ]);

        // Admin owns everything
        $this->assertTrue($product->isOwnedBy($admin));

        // Matching producer owns product
        $this->assertTrue($product->isOwnedBy($producerUser));

        // Other producer does not own product
        $otherProducerUser = User::factory()->role('producer')->create();
        Producer::factory()->create(['user_id' => $otherProducerUser->id]);
        $this->assertFalse($product->isOwnedBy($otherProducerUser));

        // Matching transformer owns product
        $this->assertTrue($product->isOwnedBy($transformerUser));

        // Consumer never owns product
        $this->assertFalse($product->isOwnedBy($consumer));
    }
}
