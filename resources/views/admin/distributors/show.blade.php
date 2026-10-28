@extends('layouts.backend')

@section('title', 'Détail distributeur')
@section('page-title', 'Détail distributeur')

@section('content')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.distributors.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.distributors.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.distributors.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Entreprise</dt><dd>{{ $item->company_name ?? '—' }}</dd></div>
<div><dt>Type</dt><dd>{{ $item->distributor_type ?? '—' }}</dd></div>
<div><dt>Couverture</dt><dd>{{ $item->coverage_area ?? '—' }}</dd></div>
<div><dt>Traces</dt><dd>{{ $item->supply_chain_traces_count ?? '—' }}</dd></div>

        </dl>
    </div>
    
@endsection