<x-guest-layout>
    <div class="auth-header">
        <h2 class="auth-title">Vérification en deux étapes</h2>
        <p class="auth-subtitle" data-hint-code>Saisissez le code à 6 chiffres affiché par votre application d’authentification.</p>
        <p class="auth-subtitle" data-hint-recovery hidden>Saisissez un de vos codes de récupération.</p>
    </div>

    <form method="POST" action="{{ url('/two-factor-challenge') }}">
        @csrf
        <div class="form-group" data-field-code>
            <label for="code" class="form-label">Code</label>
            <input id="code" class="form-input" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus placeholder="123456">
            @error('code')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group" data-field-recovery hidden>
            <label for="recovery_code" class="form-label">Code de récupération</label>
            <input id="recovery_code" class="form-input" type="text" name="recovery_code" autocomplete="one-time-code" placeholder="abcd1-efgh2">
            @error('recovery_code')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn-primary">Se connecter</button>
    </form>

    <div class="form-links">
        <button type="button" class="form-link" style="background:none;border:0;cursor:pointer;padding:0;" data-toggle-recovery>Utiliser un code de récupération</button>
    </div>

    <script>
        (() => {
            const btn = document.querySelector('[data-toggle-recovery]');
            const parts = ['[data-field-code]', '[data-field-recovery]', '[data-hint-code]', '[data-hint-recovery]'].map(s => document.querySelector(s));
            const useRecovery = (on) => {
                parts[0].hidden = on; parts[2].hidden = on; parts[1].hidden = ! on; parts[3].hidden = ! on;
                (on ? parts[0] : parts[1]).querySelector('input').value = '';
                btn.textContent = on ? 'Utiliser le code de l’application' : 'Utiliser un code de récupération';
                (on ? parts[1] : parts[0]).querySelector('input').focus();
            };
            btn.addEventListener('click', () => useRecovery(parts[1].hidden));
            @if($errors->has('recovery_code')) useRecovery(true); @endif
        })();
    </script>
</x-guest-layout>
