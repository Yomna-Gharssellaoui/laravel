<x-guest-layout>
    <div class="auth-header">
        <h2 class="auth-title">Mot de passe oublié</h2>
        <p class="auth-subtitle">Indiquez votre email : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
    </div>

    @if (session('status'))
        <div class="status-message success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label for="email" class="form-label">Adresse email</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="votre@email.com">
            @error('email')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn-primary">Envoyer le lien de réinitialisation</button>
    </form>

    <div class="form-links">
        <a href="{{ route('login') }}" class="form-link">← Retour à la connexion</a>
    </div>
</x-guest-layout>
