<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_uploads_and_removes_a_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ]);

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_avatar_must_be_an_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('avatar');
    }

    public function test_full_two_factor_flow(): void
    {
        $user = User::factory()->create();

        // Wrong password: refused
        $this->actingAs($user)->post('/user/two-factor', ['password' => 'nope'])->assertSessionHasErrorsIn('twoFactor', 'password');
        $this->assertNull($user->fresh()->two_factor_secret);

        // Enable, then the account page shows the QR code
        $this->actingAs($user)->post('/user/two-factor', ['password' => 'password'])->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication(), 'Not active until confirmed');
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('<svg', false);

        // Confirm with a real TOTP code
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $this->actingAs($user)->post('/user/two-factor/confirm', ['code' => (new Google2FA)->getCurrentOtp($secret)])->assertRedirect();
        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());

        // Logging in now asks for the code
        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // A recovery code works once
        $recovery = $user->fresh()->recoveryCodes()[0];
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($recovery, $user->fresh()->recoveryCodes());

        // Disable
        $this->actingAs($user->fresh())->delete('/user/two-factor', ['password' => 'password']);
        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_challenge_page_needs_a_pending_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect(route('login'));
    }

    public function test_validation_messages_are_in_french(): void
    {
        $this->from('/register')->post('/register', ['name' => '', 'email' => 'x'])
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);
    }
}
