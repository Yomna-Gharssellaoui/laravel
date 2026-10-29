<x-guest-layout>
    <div class="auth-header">
        <h2 class="auth-title">Vérifiez votre email</h2>
        <p class="auth-subtitle">Merci pour votre inscription ! Cliquez sur le lien que nous venons de vous envoyer par email pour activer votre compte.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="status-message success">Un nouveau lien de vérification vient d’être envoyé à votre adresse email.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn-primary">Renvoyer l’email de vérification</button>
    </form>

    <div class="form-links">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="form-link" style="background:none;border:0;cursor:pointer;padding:0;">Se déconnecter</button>
        </form>
    </div>
</x-guest-layout>
