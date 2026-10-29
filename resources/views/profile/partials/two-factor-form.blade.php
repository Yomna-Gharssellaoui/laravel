@php
    $pending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;
    $active = $user->hasEnabledTwoFactorAuthentication();
    $showCodes = $active && in_array(session('status'), ['two-factor-confirmed', 'recovery-codes-generated'], true);
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Authentification à deux facteurs (2FA)</h2>
        <p class="mt-1 text-sm text-gray-600">
            Ajoutez un code temporaire, généré par une application comme Google Authenticator ou Microsoft Authenticator, à votre mot de passe.
        </p>
    </header>

    <div class="mt-4">
        @if($active)
            <p class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">● Activée depuis le {{ $user->two_factor_confirmed_at->format('d/m/Y') }}</p>
        @elseif($pending)
            <p class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800">En attente de confirmation</p>
        @else
            <p class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">Désactivée</p>
        @endif
    </div>

    @if($pending)
        <div class="mt-6 space-y-4">
            <p class="text-sm text-slate-700">1. Scannez ce QR code avec votre application d’authentification :</p>
            <div class="inline-block rounded-xl border border-slate-200 bg-white p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
            <p class="text-sm text-slate-600">Ou saisissez la clé manuellement :
                <code class="rounded bg-slate-100 px-2 py-1 font-mono text-slate-800">{{ decrypt($user->two_factor_secret) }}</code>
            </p>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label for="code" value="2. Code à 6 chiffres affiché par l’application" />
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="tv-input mt-1 w-40 font-mono tracking-widest" required>
                </div>
                <button type="submit" class="tv-btn-primary">Confirmer</button>
            </form>
            <x-input-error :messages="$errors->confirmTwoFactorAuthentication->get('code')" />
        </div>
    @endif

    @if($showCodes)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-900">Codes de récupération — notez-les maintenant</p>
            <p class="mt-1 text-sm text-amber-800">Chaque code permet de vous connecter une seule fois si vous perdez votre téléphone.</p>
            <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-slate-800">
                @foreach($user->recoveryCodes() as $code)
                    <span class="rounded bg-white px-2 py-1">{{ $code }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6 flex flex-wrap items-end gap-3">
        @if(! $user->two_factor_secret)
            <form method="POST" action="{{ route('two-factor.enable') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <x-input-label for="tf_password_enable" value="Mot de passe actuel" />
                    <input id="tf_password_enable" type="password" name="password" class="tv-input mt-1 w-56" autocomplete="current-password" required>
                </div>
                <button type="submit" class="tv-btn-primary">Activer la 2FA</button>
            </form>
        @else
            @if($active)
                <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="tf_password_codes" value="Mot de passe actuel" />
                        <input id="tf_password_codes" type="password" name="password" class="tv-input mt-1 w-56" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="tv-btn-secondary">Nouveaux codes de récupération</button>
                </form>
            @endif
            <form method="POST" action="{{ route('two-factor.disable') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @method('DELETE')
                <div>
                    <x-input-label for="tf_password_disable" value="Mot de passe actuel" />
                    <input id="tf_password_disable" type="password" name="password" class="tv-input mt-1 w-56" autocomplete="current-password" required>
                </div>
                <button type="submit" class="tv-btn-danger">{{ $active ? 'Désactiver la 2FA' : 'Annuler' }}</button>
            </form>
        @endif
    </div>
    <x-input-error :messages="$errors->twoFactor->get('password')" class="mt-2" />
</section>
