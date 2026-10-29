<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Second step of the login when the account has two-factor authentication:
 * a TOTP code from the authenticator app, or one of the recovery codes.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->challengedUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        if ($recovery = $request->input('recovery_code')) {
            $valid = collect($user->recoveryCodes())->first(fn ($code) => hash_equals($code, trim($recovery)));

            if (! $valid) {
                throw ValidationException::withMessages(['recovery_code' => 'Ce code de récupération est invalide.']);
            }

            // A recovery code can only be used once
            $user->replaceRecoveryCode($valid);
        } else {
            $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

            if (! $provider->verify($secret, preg_replace('/\s+/', '', (string) $request->input('code')))) {
                throw ValidationException::withMessages(['code' => 'Ce code est invalide ou a expiré.']);
            }
        }

        Auth::guard('web')->login($user, (bool) $request->session()->pull('login.remember', false));
        $request->session()->forget('login.id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function challengedUser(Request $request): ?User
    {
        $id = $request->session()->get('login.id');

        return $id ? User::find($id) : null;
    }
}
