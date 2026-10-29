@php($item = $item ?? null)
@php($selected = old('preferences', $item?->preferences ?? []))
<div class="grid gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="user_id" value="Compte consommateur" />
        <select name="user_id" id="user_id" class="tv-input mt-1" required>
            <option value="">—</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('user_id', $item?->user_id ?? '') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="sustainability_level" value="Niveau de durabilité" />
        <select name="sustainability_level" id="sustainability_level" class="tv-input mt-1">
            <option value="">—</option>
            @foreach(\App\Models\Consumer::LEVELS as $level)
                <option value="{{ $level }}" @selected(old('sustainability_level', $item?->sustainability_level) === $level)>{{ $level }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="budget_range" value="Budget" />
        <select name="budget_range" id="budget_range" class="tv-input mt-1">
            <option value="">—</option>
            @foreach(\App\Models\Consumer::BUDGETS as $budget)
                <option value="{{ $budget }}" @selected(old('budget_range', $item?->budget_range) === $budget)>{{ $budget }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <x-input-label value="Préférences" />
        <div class="mt-2 flex flex-wrap gap-4">
            @foreach(\App\Models\Consumer::PREFERENCES as $key => $label)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="preferences[]" value="{{ $key }}" class="rounded border-slate-300" @checked(in_array($key, $selected, true))> {{ $label }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('preferences.*')" class="mt-1" />
    </div>
    <x-form.field label="Restrictions alimentaires (virgules)" name="dietary_restrictions" :value="old('dietary_restrictions', implode(', ', $item?->dietary_restrictions ?? []))" />
    <x-form.field label="Allergies (virgules, comparées aux ingrédients)" name="allergies" :value="old('allergies', implode(', ', $item?->allergies ?? []))" />
</div>
