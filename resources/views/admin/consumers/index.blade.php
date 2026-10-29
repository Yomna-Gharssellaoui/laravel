@extends('layouts.backend')

@section('title', 'Consommateurs')
@section('page-title', 'Consommateurs')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
            
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        <a href="{{ route('admin.consumers.create') }}" class="tv-btn-primary">Ajouter</a>
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th><th>Niveau</th><th>Budget</th><th>Évaluations</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->user?->name ?? '—' }}</td><td>{{ $item->sustainability_level ?? '—' }}</td><td>{{ $item->budget_range ?? '—' }}</td><td>{{ $item->personal_ratings_count ?? '—' }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.consumers.show', $item) }}" class="tv-link">Voir</a>
                                <a href="{{ route('admin.consumers.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                <form action="{{ route('admin.consumers.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun consommateur" message="Commencez par créer une entrée." />
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