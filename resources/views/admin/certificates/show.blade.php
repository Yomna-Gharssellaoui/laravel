@extends('layouts.backend')

@section('title', 'Détail certification')
@section('page-title', 'Détail certification')

@section('content')
    <div class="flex gap-3 mb-6">
        @can('update', $item)
            <a href="{{ route('admin.certificates.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        @endcan
        <a href="{{ route('admin.certificates.index') }}" class="tv-btn-secondary">Retour</a>
        @can('delete', $item)
            <form action="{{ route('admin.certificates.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
                @csrf @method('DELETE')
                <button class="tv-btn-danger" type="submit">Supprimer</button>
            </form>
        @endcan
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
<div><dt>Type</dt><dd>{{ $item->certificate_type ?? '—' }}</dd></div>
<div><dt>Émetteur</dt><dd>{{ $item->issuer ?? '—' }}</dd></div>
<div><dt>Numéro</dt><dd>{{ $item->certificate_number ?? '—' }}</dd></div>
<div><dt>Statut</dt><dd><x-status-badge :status="$item->status" /></dd></div>
<div><dt>Émise le</dt><dd>{{ $item->issue_date?->format('d/m/Y') ?? '—' }}</dd></div>
<div><dt>Expiration</dt><dd><x-expiry-badge :certificate="$item" /></dd></div>
<div><dt>Document</dt><dd>@if($item->document_path)<a href="{{ $item->documentUrl() }}" target="_blank" class="tv-link">Ouvrir le certificat</a>@else — @endif</dd></div>

        </dl>
    </div>
    
@endsection