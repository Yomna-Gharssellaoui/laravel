@extends('layouts.backend')

@section('title', 'Modèles ML')
@section('page-title', 'Modèles de machine learning')

@section('content')
    <div class="tv-card mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-600">Un modèle scikit-learn par module, entraîné et exécuté localement par le service Python <code>ml/</code>, sans API externe.</p>
                <p class="mt-1 text-xs text-slate-600">Données d’entraînement synthétiques et reproductibles (graine 42), décrites dans chaque fichier <code>ml/modules/*.py</code>. Réentraîner : <code>cd ml</code> puis <code>python train_all.py</code>.</p>
            </div>
            @if($health)
                <x-ml-badge level="good">Service ML démarré · {{ collect($health['models'])->filter()->count() }}/{{ count($health['models']) }} modèles chargés</x-ml-badge>
            @elseif(! $enabled)
                <x-ml-badge level="warn">Service ML désactivé (ML_SERVICE_URL vide)</x-ml-badge>
            @else
                <x-ml-badge level="bad">Service ML arrêté : cd ml puis uvicorn service:app --port 8001</x-ml-badge>
            @endif
        </div>
    </div>

    @if($models === [])
        <x-empty-state title="Aucun modèle à afficher" message="Démarrez le service ML pour voir les modèles et leurs scores." />
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach($models as $model)
                <div class="tv-card border-l-4 border-violet-400">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-violet-700">Module {{ $model['module'] ?? '?' }}</p>
                            <h3 class="mt-1 font-display text-xl font-semibold text-brand-900">{{ $model['title'] ?? $model['name'] }}</h3>
                        </div>
                        @if(isset($model['trained_at']))
                            <x-ml-badge level="good">Entraîné le {{ \Illuminate\Support\Carbon::parse($model['trained_at'])->format('d/m/Y H:i') }}</x-ml-badge>
                        @else
                            <x-ml-badge level="bad">Non entraîné</x-ml-badge>
                        @endif
                    </div>

                    @isset($model['algorithm'])
                        <p class="mt-3 text-sm text-slate-700">{{ $model['description'] }}</p>
                        <dl class="mt-3 grid gap-1 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-slate-600">Algorithme</dt><dd class="text-right font-medium">{{ $model['algorithm'] }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-600">Données d’entraînement</dt><dd class="font-medium">{{ number_format($model['dataset_rows'], 0, ',', ' ') }} lignes</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-600">Utilisé dans</dt><dd class="text-right">{{ $usedIn[$model['name']] ?? '—' }}</dd></div>
                        </dl>

                        <h4 class="mt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Évaluation (jeu de test mis de côté)</h4>
                        <table class="mt-2 w-full text-sm">
                            @foreach($model['metrics'] as $key => $value)
                                <tr class="border-t border-slate-100">
                                    <td class="py-1 text-slate-600">{{ $labels[$key] ?? $key }}</td>
                                    <td class="py-1 text-right font-semibold">
                                        @if(is_array($value))
                                            {{ collect($value)->map(fn ($n, $k) => "$k : $n")->implode(' · ') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endisset
                </div>
            @endforeach
        </div>
    @endif
@endsection
