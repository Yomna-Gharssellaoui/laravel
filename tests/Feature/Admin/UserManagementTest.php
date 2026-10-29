<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_user_with_a_role_and_a_verification_email_is_sent(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Nouvelle productrice',
            'email' => 'prod@example.com',
            'role' => 'producer',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect();

        $user = User::where('email', 'prod@example.com')->firstOrFail();
        $this->assertSame('producer', $user->role);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_role_cannot_be_chosen_at_registration(): void
    {
        $this->post('/register', [
            'name' => 'Pirate',
            'email' => 'pirate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame('consumer', User::where('email', 'pirate@example.com')->value('role'));
    }

    public function test_admin_can_change_a_role_and_block_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'distributor',
        ])->assertRedirect();
        $this->assertSame('distributor', $user->fresh()->role);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/block")->assertRedirect();
        $this->assertTrue($user->fresh()->isBlocked());

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/block");
        $this->assertFalse($user->fresh()->isBlocked());
    }

    public function test_admin_cannot_block_demote_or_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/block");
        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'consumer',
        ])->assertSessionHasErrors('role');
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $fresh = $admin->fresh();
        $this->assertNotNull($fresh);
        $this->assertFalse($fresh->isBlocked());
        $this->assertSame('admin', $fresh->role);
    }
}
