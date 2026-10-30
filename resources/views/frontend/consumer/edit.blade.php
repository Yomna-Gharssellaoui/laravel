@extends('layouts.frontend')

@section('title', 'Modifier mes préférences')

@section('content')
    <x-page-header eyebrow="Mon espace" title="Modifier mes préférences" subtitle="Ces informations personnalisent vos recommandations ; elles ne constituent pas un diagnostic médical." />

    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('consumer.update') }}" class="tv-card space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label value="Préférences" />
                @php($selected = old('preferences', $consumer->preferences ?? []))
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach(\App\Models\Consumer::PREFERENCES as $key => $label)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <input type="checkbox" name="preferences[]" value="{{ $key }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(in_array($key, $selected, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('preferences.*')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="sustainability_level" value="Niveau de durabilité" />
                    <select id="sustainability_level" name="sustainability_level" class="tv-input mt-1 w-full">
                        @foreach(\App\Models\Consumer::LEVELS as $level)
                            <option value="{{ $level }}" @selected(old('sustainability_level', $consumer->sustainability_level) === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('sustainability_level')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="budget_range" value="Budget" />
                    <select id="budget_range" name="budget_range" class="tv-input mt-1 w-full">
                        @foreach(\App\Models\Consumer::BUDGETS as $range)
                            <option value="{{ $range }}" @selected(old('budget_range', $consumer->budget_range) === $range)>{{ $range }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('budget_range')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="dietary_restrictions" value="Restrictions alimentaires" />
                    <input id="dietary_restrictions" name="dietary_restrictions" value="{{ old('dietary_restrictions', implode(', ', $consumer->dietary_restrictions ?? [])) }}" class="tv-input mt-1 w-full" placeholder="végétarien, sans gluten">
                </div>
                <div>
                    <x-input-label for="allergies" value="Allergies" />
                    <input id="allergies" name="allergies" value="{{ old('allergies', implode(', ', $consumer->allergies ?? [])) }}" class="tv-input mt-1 w-full" placeholder="gluten, lactose">
                    <p class="mt-1 text-xs text-slate-600">Les produits contenant une de vos allergies sont écartés.</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('consumer.profile') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </section>
@endsection
