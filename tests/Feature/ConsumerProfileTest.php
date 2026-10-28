<?php

namespace Tests\Feature;

use App\Models\Consumer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_edit_and_update_persists()
    {
        $user = User::factory()->create();
        $consumer = Consumer::factory()->for($user, 'user')->create([
            'preferences' => ['local'],
            'dietary_restrictions' => [],
            'allergies' => [],
            'budget_range' => 'moyen',
        ]);

        $this->actingAs($user)
            ->get(route('consumer.edit'))
            ->assertStatus(200)
            ->assertSee('Modifier mes préférences');

        $payload = [
            'preferences' => ['local', 'bio', 'faible_co2'],
            'sustainability_level' => 'engagé',
            'dietary_restrictions' => 'végétarien',
            'allergies' => 'gluten',
            'budget_range' => 'éco',
        ];

        $this->actingAs($user)
            ->patch(route('consumer.update'), $payload)
            ->assertRedirect(route('consumer.profile'));

        $this->assertDatabaseHas('consumers', [
            'id' => $consumer->id,
            'budget_range' => 'éco',
        ]);

        $fresh = $consumer->fresh();
        $this->assertSame('engagé', $fresh->sustainability_level);
        $this->assertSame(['local', 'bio', 'faible_co2'], $fresh->preferences);
        $this->assertSame(['gluten'], $fresh->allergies);
    }
}
