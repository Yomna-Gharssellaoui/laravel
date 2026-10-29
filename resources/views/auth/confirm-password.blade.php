<x-guest-layout>
    <div class="auth-header">
        <h2 class="auth-title">Confirmation</h2>
        <p class="auth-subtitle">Zone sécurisée : confirmez votre mot de passe pour continuer.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="form-group">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn-primary">Confirmer</button>
    </form>
</x-guest-layout>
