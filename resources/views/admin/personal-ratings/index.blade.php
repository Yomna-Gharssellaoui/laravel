@extends('layouts.backend')

@section('title', 'Évaluations personnalisées')
@section('page-title', 'Évaluations personnalisées')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
            
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        <form method="POST" action="{{ route('admin.personal-ratings.recompute') }}">
            @csrf
            <button type="submit" class="tv-btn-primary">Recalculer toutes les recommandations</button>
        </form>
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Consommateur</th><th>Produit</th><th>Score</th><th>Raison</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->consumer?->user?->name ?? '—' }}</td><td>{{ $item->product?->name ?? '—' }}</td><td>{{ $item->personalized_score ?? '—' }}</td><td class="max-w-md truncate" title="{{ $item->reason }}">{{ $item->recommendation_reason ?? '—' }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.personal-ratings.show', $item) }}" class="tv-link">Voir</a>
                                <form action="{{ route('admin.personal-ratings.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucune évaluation" message="Cliquez sur « Recalculer » pour générer les recommandations." />
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