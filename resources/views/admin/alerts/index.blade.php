@extends('layouts.backend')

@section('title', 'Alertes')
@section('page-title', 'Alertes')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
                        <select name="severity" class="tv-input">
                <option value="">Sévérité</option>
                @foreach(['low','medium','high','critical'] as $st)
                    <option value="{{ $st }}" @selected(request('severity')===$st)>{{ $st }}</option>
                @endforeach
            </select>
            <select name="status" class="tv-input">
                <option value="">Statut</option>
                @foreach(['open','investigating','resolved','dismissed'] as $st)
                    <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
                @endforeach
            </select>
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        <a href="{{ route('admin.alerts.create') }}" class="tv-btn-primary">Ajouter</a>
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Titre</th><th>Type</th><th>Sévérité</th><th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->title ?? '—' }}</td><td>{{ $item->alert_type ?? '—' }}</td><td><x-status-badge :status="$item->severity ?? '—'" /></td><td><x-status-badge :status="$item->status ?? '—'" /></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.alerts.show', $item) }}" class="tv-link">Voir</a>
                                <a href="{{ route('admin.alerts.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                <form action="{{ route('admin.alerts.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun alerte" message="Commencez par créer une entrée." />
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