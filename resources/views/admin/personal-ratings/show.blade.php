@extends('layouts.backend')

@section('title', 'Détail évaluation')
@section('page-title', 'Détail évaluation')

@section('content')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.personal-ratings.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.personal-ratings.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Consommateur</dt><dd>{{ $item->consumer?->user?->name ?? '—' }}</dd></div>
<div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
<div><dt>Score</dt><dd>{{ $item->personalized_score ?? '—' }}</dd></div>
<div><dt>Résumé</dt><dd>{{ $item->recommendation_reason ?? '—' }}</dd></div>
<div class="sm:col-span-2"><dt>Détail du calcul</dt><dd>{{ $item->reason ?: 'Score basé uniquement sur l’empreinte environnementale.' }}</dd></div>

        </dl>
    </div>
    
@endsection