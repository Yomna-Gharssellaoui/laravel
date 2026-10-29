@extends('layouts.backend')

@section('title', 'Produits')
@section('page-title', 'Produits')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
            <select name="status" class="tv-input">
                <option value="">Tous les statuts</option>
                @foreach(\App\Models\Product::STATUSES as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        @can('create', \App\Models\Product::class)
            <a href="{{ route('admin.products.create') }}" class="tv-btn-primary">Ajouter</a>
        @endcan
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Nom</th><th>Catégorie</th><th>Statut</th><th>Producteur</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->name ?? '—' }}</td><td>{{ $item->category ?? '—' }}</td><td><x-status-badge :status="$item->status ?? '—'" /></td><td>{{ $item->producer?->company_name ?? '—' }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.products.show', $item) }}" class="tv-link">Voir</a>
                                @can('update', $item)
                                <a href="{{ route('admin.products.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                @endcan
                                @can('delete', $item)
                                <form action="{{ route('admin.products.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun produit" message="Commencez par créer une entrée." />
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