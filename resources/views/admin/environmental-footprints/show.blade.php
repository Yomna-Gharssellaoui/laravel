@extends('layouts.backend')

@section('title', 'Détail empreinte')
@section('page-title', 'Détail empreinte')

@section('content')
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
    
@endsection