<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_reviews_by_status_rating_and_query(): void
    {
        $prod1 = Product::factory()->create(['name' => 'Fromage de Chèvre']);
        $prod2 = Product::factory()->create(['name' => 'Yaourt Nature']);

        $rev1 = Review::factory()->create([
            'product_id' => $prod1->id,
            'title' => 'Goût authentique et crémeux',
            'status' => 'pending',
            'rating' => 5,
        ]);
        $rev2 = Review::factory()->create([
            'product_id' => $prod2->id,
            'title' => 'Un peu acide',
            'status' => 'approved',
            'rating' => 2,
        ]);

        // Filter by status
        $this->actingAs($this->admin)->get('/admin/reviews?status=pending')
            ->assertOk()
            ->assertSee('Goût authentique et crémeux')
            ->assertDontSee('Un peu acide');

        // Filter by rating
        $this->actingAs($this->admin)->get('/admin/reviews?rating=5')
            ->assertOk()
            ->assertSee('Goût authentique et crémeux')
            ->assertDontSee('Un peu acide');

        // Search by review title
        $this->actingAs($this->admin)->get('/admin/reviews?q=crémeux')
            ->assertOk()
            ->assertSee('Goût authentique et crémeux')
            ->assertDontSee('Un peu acide');

        // Search by product name
        $this->actingAs($this->admin)->get('/admin/reviews?q=Chèvre')
            ->assertOk()
            ->assertSee('Goût authentique et crémeux')
            ->assertDontSee('Un peu acide');
    }

    public function test_admin_can_view_single_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->admin)->get("/admin/reviews/{$review->id}")
            ->assertOk()
            ->assertSee($review->title);
    }

    public function test_admin_can_moderate_review_to_approved_rejected_and_pending(): void
    {
        $review = Review::factory()->create(['status' => 'pending']);

        // Approve
        $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/moderate", [
            'status' => 'approved',
            'verified_purchase' => true,
        ])->assertRedirect()
          ->assertSessionHas('success', 'Avis approuvé et publié.');

        $this->assertSame('approved', $review->fresh()->status);
        $this->assertTrue($review->fresh()->verified_purchase);

        // Reject
        $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/moderate", [
            'status' => 'rejected',
        ])->assertRedirect()
          ->assertSessionHas('success', 'Avis refusé.');

        $this->assertSame('rejected', $review->fresh()->status);

        // Set back to pending
        $this->actingAs($this->admin)->patch("/admin/reviews/{$review->id}/moderate", [
            'status' => 'pending',
        ])->assertRedirect()
          ->assertSessionHas('success', 'Avis remis en attente.');

        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_admin_can_delete_a_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/reviews/{$review->id}")
            ->assertRedirect(route('admin.reviews.index'))
            ->assertSessionHas('success', 'Avis supprimé.');

        $this->assertModelMissing($review);
    }
}
