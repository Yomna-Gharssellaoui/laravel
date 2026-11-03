<?php

namespace Tests\Feature\Admin;

use App\Models\Consumer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_consumers_by_user_name_query(): void
    {
        $userAlice = User::factory()->create(['name' => 'Alice Martin', 'role' => 'consumer']);
        $userBob = User::factory()->create(['name' => 'Bob Dupont', 'role' => 'consumer']);

        Consumer::factory()->for($userAlice, 'user')->create();
        Consumer::factory()->for($userBob, 'user')->create();

        $this->actingAs($this->admin)->get('/admin/consumers?q=Alice')
            ->assertOk()
            ->assertSee('Alice Martin')
            ->assertDontSee('Bob Dupont');
    }

    public function test_admin_can_filter_consumers_by_user_email_query(): void
    {
        $userAlice = User::factory()->create(['email' => 'alice@example.com', 'role' => 'consumer']);
        $userBob = User::factory()->create(['email' => 'bob@example.com', 'role' => 'consumer']);

        Consumer::factory()->for($userAlice, 'user')->create();
        Consumer::factory()->for($userBob, 'user')->create();

        $this->actingAs($this->admin)->get('/admin/consumers?q=bob@example.com')
            ->assertOk()
            ->assertSee('bob@example.com')
            ->assertDontSee('alice@example.com');
    }

    public function test_admin_can_view_create_and_edit_screens(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $consumer = Consumer::factory()->for($user, 'user')->create();

        $this->actingAs($this->admin)->get('/admin/consumers/create')
            ->assertOk();

        $this->actingAs($this->admin)->get("/admin/consumers/{$consumer->id}")
            ->assertOk()
            ->assertSee($user->name);

        $this->actingAs($this->admin)->get("/admin/consumers/{$consumer->id}/edit")
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_admin_can_create_a_consumer_profile(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);

        $response = $this->actingAs($this->admin)->post('/admin/consumers', [
            'user_id' => $user->id,
            'preferences' => ['bio', 'local'],
            'sustainability_level' => 'engagé',
            'budget_range' => 'moyen',
            'dietary_restrictions' => 'végétarien',
            'allergies' => 'gluten',
        ]);

        $consumer = Consumer::where('user_id', $user->id)->firstOrFail();

        $response->assertRedirect(route('admin.consumers.show', $consumer))
            ->assertSessionHas('success', 'Profil consommateur créé et recommandations calculées.');

        $this->assertSame(['bio', 'local'], $consumer->preferences);
        $this->assertSame('engagé', $consumer->sustainability_level);
        $this->assertSame('moyen', $consumer->budget_range);
        $this->assertSame(['végétarien'], $consumer->dietary_restrictions);
        $this->assertSame(['gluten'], $consumer->allergies);
    }

    public function test_admin_create_validates_user_must_have_consumer_role_and_be_unique(): void
    {
        $producer = User::factory()->role('producer')->create();

        $this->actingAs($this->admin)->post('/admin/consumers', [
            'user_id' => $producer->id,
            'preferences' => ['bio'],
        ])->assertSessionHasErrors('user_id');

        $existingUser = User::factory()->create(['role' => 'consumer']);
        Consumer::factory()->for($existingUser, 'user')->create();

        $this->actingAs($this->admin)->post('/admin/consumers', [
            'user_id' => $existingUser->id,
            'preferences' => ['bio'],
        ])->assertSessionHasErrors('user_id');
    }

    public function test_admin_can_update_a_consumer_profile(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $consumer = Consumer::factory()->for($user, 'user')->create([
            'preferences' => ['local'],
            'sustainability_level' => 'débutant',
            'budget_range' => 'éco',
            'dietary_restrictions' => [],
            'allergies' => [],
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/consumers/{$consumer->id}", [
            'user_id' => $user->id,
            'preferences' => ['faible_co2'],
            'sustainability_level' => 'expert',
            'budget_range' => 'premium',
            'dietary_restrictions' => 'vegan, sans sel',
            'allergies' => 'lactose, arachides',
        ]);

        $response->assertRedirect(route('admin.consumers.show', $consumer))
            ->assertSessionHas('success', 'Profil consommateur mis à jour et recommandations recalculées.');

        $fresh = $consumer->fresh();
        $this->assertSame(['faible_co2'], $fresh->preferences);
        $this->assertSame('expert', $fresh->sustainability_level);
        $this->assertSame('premium', $fresh->budget_range);
        $this->assertSame(['vegan', 'sans sel'], $fresh->dietary_restrictions);
        $this->assertSame(['lactose', 'arachides'], $fresh->allergies);
    }

    public function test_admin_can_delete_a_consumer_profile(): void
    {
        $user = User::factory()->create(['role' => 'consumer']);
        $consumer = Consumer::factory()->for($user, 'user')->create();

        $this->actingAs($this->admin)->delete("/admin/consumers/{$consumer->id}")
            ->assertRedirect(route('admin.consumers.index'))
            ->assertSessionHas('success', 'Profil consommateur supprimé.');

        $this->assertModelMissing($consumer);
    }
}
