<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

/**
 * Two-factor management from the account page, using Fortify's actions
 * (secret generation, encryption, TOTP verification, recovery codes).
 */
class TwoFactorController extends Controller
{
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $enable($request->user());

        return back()->with('status', 'two-factor-enabled');
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $confirm($request->user(), preg_replace('/\s+/', '', $request->input('code')));

        return back()->with('status', 'two-factor-confirmed');
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $generate($request->user());

        return back()->with('status', 'recovery-codes-generated');
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $disable($request->user());

        return back()->with('status', 'two-factor-disabled');
    }
}
