@extends('layouts.backend')

@section('title', 'Détail utilisateur')
@section('page-title', 'Détail utilisateur')

@section('content')
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('admin.users.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.users.index') }}" class="tv-btn-secondary">Retour</a>
        @unless($item->hasVerifiedEmail())
            <form action="{{ route('admin.users.verification', $item) }}" method="POST">
                @csrf
                <button class="tv-btn-secondary" type="submit">Renvoyer l’email de vérification</button>
            </form>
        @endunless
        @unless($item->is(auth()->user()))
            <form action="{{ route('admin.users.block', $item) }}" method="POST">
                @csrf @method('PATCH')
                <button class="tv-btn-secondary" type="submit">{{ $item->isBlocked() ? 'Réactiver le compte' : 'Suspendre le compte' }}</button>
            </form>
            <form action="{{ route('admin.users.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer définitivement ce compte ?');">
                @csrf @method('DELETE')
                <button class="tv-btn-danger" type="submit">Supprimer</button>
            </form>
        @endunless
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Nom</dt><dd>{{ $item->name }}</dd></div>
            <div><dt>Email</dt><dd>{{ $item->email }}</dd></div>
            <div><dt>Rôle</dt><dd><x-status-badge :status="$item->role" /></dd></div>
            <div><dt>Email vérifié</dt><dd>{{ $item->email_verified_at?->format('d/m/Y H:i') ?? 'Non' }}</dd></div>
            <div><dt>État</dt><dd>{{ $item->isBlocked() ? 'Suspendu depuis le '.$item->blocked_at->format('d/m/Y') : 'Actif' }}</dd></div>
            <div><dt>Inscrit le</dt><dd>{{ $item->created_at?->format('d/m/Y') }}</dd></div>
            <div><dt>Profil métier</dt><dd>
                @if($item->producer) Producteur : <a class="tv-link" href="{{ route('admin.producers.show', $item->producer) }}">{{ $item->producer->company_name }}</a>
                @elseif($item->transformer) Transformateur : <a class="tv-link" href="{{ route('admin.transformers.show', $item->transformer) }}">{{ $item->transformer->company_name }}</a>
                @elseif($item->distributor) Distributeur : <a class="tv-link" href="{{ route('admin.distributors.show', $item->distributor) }}">{{ $item->distributor->company_name }}</a>
                @elseif($item->consumer) <a class="tv-link" href="{{ route('admin.consumers.show', $item->consumer) }}">Profil consommateur</a>
                @else — @endif
            </dd></div>
            <div><dt>Avis publiés</dt><dd>{{ $item->reviews_count }}</dd></div>
        </dl>
    </div>
@endsection
