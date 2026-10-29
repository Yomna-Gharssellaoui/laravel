@extends('layouts.backend')

@section('title', 'Certifications')
@section('page-title', 'Certifications')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
                        <select name="status" class="tv-input">
                <option value="">Tous statuts</option>
                @foreach(['pending','verified','expired','rejected'] as $st)
                    <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
                @endforeach
            </select>
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        @can('create', \App\Models\Certificate::class)
            <a href="{{ route('admin.certificates.create') }}" class="tv-btn-primary">Ajouter</a>
        @endcan
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Produit</th><th>Type</th><th>Émetteur</th><th>Statut</th><th>Expiration</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? '—' }}</td><td>{{ $item->certificate_type ?? '—' }}</td><td>{{ $item->issuer ?? '—' }}</td><td><x-status-badge :status="$item->status ?? '—'" /></td><td><x-expiry-badge :certificate="$item" /></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.certificates.show', $item) }}" class="tv-link">Voir</a>
                                @can('update', $item)
                                <a href="{{ route('admin.certificates.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                @endcan
                                @can('delete', $item)
                                <form action="{{ route('admin.certificates.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun certification" message="Commencez par créer une entrée." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $items->links() }}</div>
        @endif
    </div>
@endsection