<x-guest-layout>
    <div class="auth-header">
        <h2 class="auth-title">Nouveau mot de passe</h2>
        <p class="auth-subtitle">Choisissez un nouveau mot de passe pour votre compte.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label for="email" class="form-label">Adresse email</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Nouveau mot de passe</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-primary">Réinitialiser le mot de passe</button>
    </form>
</x-guest-layout>
