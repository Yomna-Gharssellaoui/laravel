@extends('layouts.backend')

@section('title', 'Détail producteur')
@section('page-title', 'Détail producteur')

@section('content')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.producers.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.producers.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.producers.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Entreprise</dt><dd>{{ $item->company_name ?? '—' }}</dd></div>
<div><dt>Lieu</dt><dd>{{ $item->location ?? '—' }}</dd></div>
<div><dt>Méthode</dt><dd>{{ $item->farming_method ?? '—' }}</dd></div>
<div><dt>Produits</dt><dd>{{ $item->products_count ?? '—' }}</dd></div>

        </dl>
    </div>
    
@endsection