@extends('layouts.backend')

@section('title', 'Détail alerte')
@section('page-title', 'Détail alerte')

@section('content')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.alerts.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.alerts.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.alerts.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Titre</dt><dd>{{ $item->title ?? '—' }}</dd></div>
<div><dt>Type</dt><dd>{{ $item->alert_type ?? '—' }}</dd></div>
<div><dt>Sévérité</dt><dd>{{ $item->severity ?? '—' }}</dd></div>
<div><dt>Statut</dt><dd>{{ $item->status ?? '—' }}</dd></div>
<div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
<div><dt>Détectée le</dt><dd>{{ $item->detected_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
<div><dt>Vérification liée</dt><dd>@if($item->complianceCheck)<a class="tv-link" href="{{ route('admin.compliance-checks.show', $item->complianceCheck) }}">{{ $item->complianceCheck->check_type }} — {{ $item->complianceCheck->environmentalClaim?->title }}</a>@else — @endif</dd></div>
<div class="sm:col-span-2"><dt>Description</dt><dd>{{ $item->description ?? '—' }}</dd></div>
<div class="sm:col-span-2"><dt>Résolution</dt><dd>{{ $item->resolution ?? '—' }}</dd></div>

        </dl>
    </div>
    
@endsection