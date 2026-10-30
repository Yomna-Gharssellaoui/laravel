@extends('layouts.frontend')

@section('title', 'Outils IA')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">Outils IA</p>
            <h1 class="mt-2 font-display text-4xl font-semibold text-brand-900">Vérifiez par vous-même</h1>
            <p class="mt-3 text-slate-600">
                Deux outils pour consommer en connaissance de cause. Ils s’appuient sur les modèles de machine learning
                de Trace Verte, entraînés et exécutés sur nos serveurs : vos textes ne sont envoyés à aucun service externe.
            </p>
        </div>

        @unless($available)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Les outils IA sont momentanément indisponibles.</div>
        @endunless

        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            {{-- M5: greenwashing checker --}}
            <section class="tv-card" id="greenwashing">
                <h2 class="font-display text-2xl font-semibold text-brand-900">Détecteur de greenwashing</h2>
                <p class="mt-2 text-sm text-slate-600">Collez une allégation lue sur un emballage ou une publicité : le modèle estime si elle est vague ou vérifiable, et montre les mots en cause.</p>

                <form method="POST" action="{{ route('ai-tools.greenwashing') }}#greenwashing" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="claim" rows="3" maxlength="1000" required class="tv-input w-full" placeholder="Ex. : 100 % naturel et respectueux de la planète" aria-label="Allégation à vérifier">{{ old('claim', $claim ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('claim')" />
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="tv-btn-primary">Analyser</button>
                        @foreach($examples as $example)
                            <button type="button" class="rounded-full border border-brand-200 px-3 py-1 text-xs text-brand-700 hover:bg-brand-50" data-example="{{ $example }}">{{ \Illuminate\Support\Str::limit($example, 34) }}</button>
                        @endforeach
                    </div>
                </form>

                @isset($claim)
                    @if($greenwashing)
                        @php
                            $probability = $greenwashing['probability'];
                            $level = $probability >= 65 ? 'bad' : ($probability >= 35 ? 'warn' : 'good');
                            // Highlight the expressions the model relies on (markers first, then escaping)
                            $marked = $claim;
                            foreach ($greenwashing['risky_terms'] as $term) {
                                $marked = preg_replace('/'.preg_quote($term, '/').'/iu', "\u{1}$0\u{2}", $marked, 1);
                            }
                            foreach ($greenwashing['reassuring_terms'] as $term) {
                                $marked = preg_replace('/'.preg_quote($term, '/').'/iu', "\u{3}$0\u{4}", $marked, 1);
                            }
                            $marked = strtr(e($marked), [
                                "\u{1}" => '<mark class="rounded bg-red-100 px-0.5 text-red-800">', "\u{2}" => '</mark>',
                                "\u{3}" => '<mark class="rounded bg-emerald-100 px-0.5 text-emerald-800">', "\u{4}" => '</mark>',
                            ]);
                        @endphp
                        <div class="mt-6 rounded-2xl border p-5 {{ ['bad' => 'border-red-200 bg-red-50', 'warn' => 'border-amber-200 bg-amber-50', 'good' => 'border-emerald-200 bg-emerald-50'][$level] }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold {{ ['bad' => 'text-red-800', 'warn' => 'text-amber-800', 'good' => 'text-emerald-800'][$level] }}">
                                    {{ ['bad' => 'Greenwashing probable', 'warn' => 'Allégation à vérifier', 'good' => 'Allégation plutôt vérifiable'][$level] }}
                                </p>
                                <span class="text-2xl font-semibold text-slate-800">{{ number_format($probability, 0) }} %</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full {{ ['bad' => 'bg-red-500', 'warn' => 'bg-amber-500', 'good' => 'bg-emerald-500'][$level] }}" style="width: {{ $probability }}%"></div>
                            </div>
                            <p class="mt-4 rounded-xl bg-white p-3 text-sm leading-relaxed text-slate-800">{!! $marked !!}</p>
                            <p class="mt-2 text-xs text-slate-500"><mark class="rounded bg-red-100 px-0.5 text-red-800">rouge</mark> : expressions vagues ou invérifiables · <mark class="rounded bg-emerald-100 px-0.5 text-emerald-800">vert</mark> : éléments vérifiables</p>
                            @if($level !== 'good')
                                <p class="mt-3 text-sm text-slate-700">Une allégation crédible cite des preuves : chiffre mesuré avec sa période, organisme certificateur, numéro de certificat, rapport consultable.</p>
                            @endif
                        </div>
                    @else
                        <p class="mt-6 text-sm text-amber-700">Analyse indisponible pour le moment, réessayez plus tard.</p>
                    @endif
                @endisset
            </section>

            {{-- M1: footprint simulator --}}
            <section class="tv-card" id="empreinte">
                <h2 class="font-display text-2xl font-semibold text-brand-900">Simulateur d’empreinte</h2>
                <p class="mt-2 text-sm text-slate-600">Décrivez un produit : le modèle estime son empreinte (CO₂, eau, sols), la compare au catalogue et indique ce qui la réduirait le plus.</p>

                @php $f = $features ?? []; @endphp
                <form method="POST" action="{{ route('ai-tools.footprint') }}#empreinte" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <label class="text-sm text-slate-700">Catégorie
                        <select name="category" class="tv-input mt-1 w-full">
                            @foreach($categories as $option)<option value="{{ $option }}" @selected(old('category', $f['category'] ?? 'huiles') === $option)>{{ ucfirst($option) }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">Mode de culture
                        <select name="farming_method" class="tv-input mt-1 w-full">
                            @foreach($farming as $option)<option value="{{ $option }}" @selected(old('farming_method', $f['farming_method'] ?? 'conventionnelle') === $option)>{{ ucfirst($option) }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">Transformation
                        <select name="transformation_type" class="tv-input mt-1 w-full">
                            @foreach($transformations as $option)<option value="{{ $option }}" @selected(old('transformation_type', $f['transformation_type'] ?? 'aucune') === $option)>{{ ucfirst($option) }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">Distance parcourue (km)
                        <input type="number" name="distance_km" min="0" max="20000" step="1" value="{{ old('distance_km', $f['distance_km'] ?? 300) }}" class="tv-input mt-1 w-full">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                        <input type="hidden" name="organic_certified" value="0">
                        <input type="checkbox" name="organic_certified" value="1" @checked(old('organic_certified', $f['organic_certified'] ?? false)) class="rounded border-slate-300 text-brand-600">
                        Certifié bio
                    </label>
                    @if($errors->hasAny(['category', 'farming_method', 'transformation_type', 'distance_km']))
                        <p class="text-sm text-red-600 sm:col-span-2">{{ $errors->first() }}</p>
                    @endif
                    <div class="sm:col-span-2"><button type="submit" class="tv-btn-primary">Estimer</button></div>
                </form>

                @isset($features)
                    @if($estimate)
                        <div class="mt-6 grid gap-3 sm:grid-cols-4">
                            @foreach(['co2_emissions' => ['CO₂', 'kg CO₂e/kg'], 'water_usage' => ['Eau', 'L/kg'], 'land_usage' => ['Sols', 'm²/kg']] as $key => [$label, $unit])
                                <div class="rounded-xl bg-brand-50 p-3">
                                    <p class="text-xs uppercase tracking-wide text-brand-700">{{ $label }}</p>
                                    <p class="mt-1 text-xl font-semibold text-brand-900">{{ $estimate[$key]['value'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $unit }} · {{ $estimate[$key]['low'] }}–{{ $estimate[$key]['high'] }}</p>
                                </div>
                            @endforeach
                            <div class="rounded-xl bg-brand-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-brand-700">Score</p>
                                <p class="mt-1 text-xl font-semibold text-brand-900">{{ $score ?? '—' }}<span class="text-sm font-normal">/100</span></p>
                                <p class="text-xs text-slate-500">100 = très sobre</p>
                            </div>
                        </div>

                        @if($improvements !== [])
                            <h3 class="mt-6 text-sm font-semibold text-slate-800">Ce qui réduirait le plus le CO₂</h3>
                            <ul class="mt-2 space-y-2 text-sm">
                                @foreach($improvements as $improvement)
                                    <li class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                                        <span>{{ $improvement['label'] }}</span>
                                        <span class="font-semibold text-emerald-700">−{{ $improvement['gain'] }} % <span class="font-normal text-slate-500">({{ $improvement['co2'] }} kg)</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($comparison->isNotEmpty())
                            <h3 class="mt-6 text-sm font-semibold text-slate-800">Produits « {{ $features['category'] }} » du catalogue</h3>
                            <ul class="mt-2 space-y-2 text-sm">
                                @foreach($comparison as $product)
                                    <li class="flex items-center justify-between gap-3">
                                        <a href="{{ route('products.show', $product) }}" class="tv-link">{{ $product->name }}</a>
                                        <span class="text-slate-600">{{ $product->environmentalFootprint->co2_emissions }} kg CO₂e/kg · score {{ $product->environmentalFootprint->ai_score }}/100</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="mt-4 text-xs text-slate-500">Estimation par Random Forest, entraînée sur 3 000 produits ; la fourchette couvre 80 % des cas.</p>
                    @else
                        <p class="mt-6 text-sm text-amber-700">Estimation indisponible pour le moment, réessayez plus tard.</p>
                    @endif
                @endisset
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-example]').forEach((button) => button.addEventListener('click', () => {
            const form = button.closest('form');
            form.querySelector('textarea').value = button.dataset.example;
            form.requestSubmit();
        }));
    </script>
@endsection
