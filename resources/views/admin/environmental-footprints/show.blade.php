@extends('layouts.backend')

@section('title', 'Détail empreinte')
@section('page-title', 'Détail empreinte')

@section('content')
    @inject('ml', 'App\Services\Ml\MlService')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.environmental-footprints.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.environmental-footprints.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.environmental-footprints.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
<div><dt>CO₂</dt><dd>{{ $item->co2_emissions !== null ? $item->co2_emissions.' kg CO₂e/kg' : '—' }}</dd></div>
<div><dt>Eau</dt><dd>{{ $item->water_usage !== null ? $item->water_usage.' L/kg' : '—' }}</dd></div>
<div><dt>Sols</dt><dd>{{ $item->land_usage !== null ? $item->land_usage.' m²/kg' : '—' }}</dd></div>
<div><dt>Score calculé</dt><dd>{{ $item->ai_score ?? '—' }}/100 ({{ $item->indicatorLevel() }})</dd></div>

        </dl>
    </div>
    
    @php($anomaly = $ml->footprintAnomaly($item))
    @php($labels = ['co2_emissions' => 'CO₂', 'water_usage' => 'Eau', 'land_usage' => 'Sols'])
    <x-ml-card module="M5" title="Détection d’anomalie" :unavailable="$anomaly === null">
        @if($anomaly)
            <p class="text-sm">
                @if($anomaly['is_anomaly'])
                    <span class="font-semibold text-red-700">Empreinte atypique pour la catégorie « {{ $item->product?->category }} »</span> (score d’anomalie {{ $anomaly['score'] }}/100) :
                    l’indicateur le plus éloigné est <strong>{{ $labels[$anomaly['main_indicator']] }}</strong>. Vérifiez la saisie ou demandez un justificatif.
                @else
                    <span class="font-semibold text-emerald-700">Empreinte cohérente</span> avec les produits de la même catégorie (score d’anomalie {{ $anomaly['score'] }}/100).
                @endif
            </p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3 text-sm">
                @foreach($anomaly['ratio_to_category_median'] as $key => $ratio)
                    <div class="rounded-lg bg-violet-50 p-3"><span class="text-slate-500">{{ $labels[$key] }}</span><br><strong>× {{ $ratio }}</strong> la médiane de la catégorie</div>
                @endforeach
            </div>
        @endif
    </x-ml-card>
@endsection
