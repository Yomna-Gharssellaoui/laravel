@php($item = $item ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <x-form.field label="Nom" name="name" :value="old('name', $item?->name)" required />
    <x-form.field label="Email" name="email" type="email" :value="old('email', $item?->email)" required />
    <div>
        <x-input-label for="role" value="Rôle" />
        <select name="role" id="role" class="tv-input mt-1" required>
            @foreach(\App\Models\User::ROLES as $role)
                <option value="{{ $role }}" @selected(old('role', $item?->role ?? 'consumer') === $role)>{{ $role }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-1" />
    </div>
    <div></div>
    <x-form.field :label="$item ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'" name="password" type="password" autocomplete="new-password" :required="! $item" />
    <x-form.field label="Confirmation du mot de passe" name="password_confirmation" type="password" autocomplete="new-password" :required="! $item" />
</div>
