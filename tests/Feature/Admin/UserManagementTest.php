<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_role_can_be_chosen_at_registration_in_testing(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="role"', false)
            ->assertSee('value="admin"', false)
            ->assertDontSee('value="admin" disabled', false);

        $this->post('/register', [
            'name' => 'Admin de test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame('admin', User::where('email', 'admin-test@example.com')->value('role'));
    }

    public function test_admin_role_cannot_be_chosen_at_registration_in_production(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $this->get('/register')
            ->assertOk()
            ->assertSee('value="admin" disabled', false);

        $this->post('/register', [
            'name' => 'Visiteur',
            'email' => 'visitor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame('consumer', User::where('email', 'visitor@example.com')->value('role'));
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

    public function test_admin_can_update_user_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['password' => 'OldPassword123!']);

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('admin.users.show', $user))
          ->assertSessionHas('success', 'Utilisateur mis à jour.');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_admin_changing_user_email_resets_email_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => 'new@example.com',
            'role' => $user->role,
        ])->assertRedirect(route('admin.users.show', $user))
          ->assertSessionHas('success', 'Utilisateur mis à jour.');

        $fresh = $user->fresh();
        $this->assertSame('new@example.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->delete("/admin/users/{$user->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Utilisateur supprimé.');

        $this->assertModelMissing($user);
    }

    public function test_cannot_resend_verification_if_user_already_verified(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/verification")
            ->assertRedirect()
            ->assertSessionHas('error', 'Cette adresse est déjà vérifiée.');

        Notification::assertNothingSent();
    }

    public function test_can_resend_verification_email_for_unverified_user(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/verification")
            ->assertRedirect()
            ->assertSessionHas('success', 'Email de vérification renvoyé.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_admin_can_filter_and_search_users_list(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice Martin', 'email' => 'alice@example.com', 'role' => 'producer']);
        $bob = User::factory()->create(['name' => 'Bob Dupont', 'email' => 'bob@example.com', 'role' => 'consumer', 'blocked_at' => now()]);
        $charlie = User::factory()->unverified()->create(['name' => 'Charlie Durand', 'email' => 'charlie@example.com', 'role' => 'transformer']);

        // Search by query term (name or email)
        $this->actingAs($admin)->get('/admin/users?q=Alice')
            ->assertOk()
            ->assertSee('Alice Martin')
            ->assertDontSee('Bob Dupont');

        $this->actingAs($admin)->get('/admin/users?q=alice@example.com')
            ->assertOk()
            ->assertSee('Alice Martin')
            ->assertDontSee('Bob Dupont');

        // Filter by role 'consumer'
        $this->actingAs($admin)->get('/admin/users?role=consumer')
            ->assertOk()
            ->assertSee('Bob Dupont')
            ->assertDontSee('Alice Martin');

        // Filter by state 'blocked'
        $this->actingAs($admin)->get('/admin/users?state=blocked')
            ->assertOk()
            ->assertSee('Bob Dupont')
            ->assertDontSee('Alice Martin');

        // Filter by state 'unverified'
        $this->actingAs($admin)->get('/admin/users?state=unverified')
            ->assertOk()
            ->assertSee('Charlie Durand');
    }
}
