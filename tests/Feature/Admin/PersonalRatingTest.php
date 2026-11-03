<?php

namespace Tests\Feature\Admin;

use App\Models\Consumer;
use App\Models\PersonalRating;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->role('admin')->create();
    }

    public function test_admin_can_list_and_search_personal_ratings(): void
    {
        $consumer = Consumer::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Huile d Olive Extra']);
        $product2 = Product::factory()->create(['name' => 'Dattes Deglet']);

        PersonalRating::create([
            'consumer_id' => $consumer->id,
            'product_id' => $product1->id,
            'personalized_score' => 4.8,
        ]);
        PersonalRating::create([
            'consumer_id' => $consumer->id,
            'product_id' => $product2->id,
            'personalized_score' => 3.2,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.personal-ratings.index', ['q' => 'Olive']));

        $response->assertOk()
            ->assertSee('Huile d Olive Extra');
    }

    public function test_admin_can_show_personal_rating(): void
    {
        $rating = PersonalRating::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.personal-ratings.show', $rating));

        $response->assertOk();
    }

    public function test_admin_can_destroy_personal_rating(): void
    {
        $rating = PersonalRating::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.personal-ratings.destroy', $rating));

        $response->assertRedirect(route('admin.personal-ratings.index'));
        $this->assertDatabaseMissing('personal_ratings', ['id' => $rating->id]);
    }
}
