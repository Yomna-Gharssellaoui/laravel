@extends('layouts.backend')

@section('title', 'Empreintes environnementales')
@section('page-title', 'Empreintes environnementales')

@section('content')
    @inject('ml', 'App\Services\Ml\MlService')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
            
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        <a href="{{ route('admin.environmental-footprints.create') }}" class="tv-btn-primary">Ajouter</a>
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Produit</th><th>CO₂ (kg)</th><th>Eau (L)</th><th>Score</th><th>Contrôle ML</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? '—' }}</td><td>{{ $item->co2_emissions ?? '—' }}</td><td>{{ $item->water_usage ?? '—' }}</td><td>{{ $item->ai_score ?? '—' }}</td>
                            <td>
                                @php($anomaly = $ml->footprintAnomaly($item))
                                @if($anomaly === null) <span class="text-xs text-slate-400">—</span>
                                @elseif($anomaly['is_anomaly']) <x-ml-badge level="bad" title="Isolation Forest (M5)">Atypique</x-ml-badge>
                                @else <x-ml-badge level="good" title="Isolation Forest (M5)">Cohérente</x-ml-badge>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.environmental-footprints.show', $item) }}" class="tv-link">Voir</a>
                                <a href="{{ route('admin.environmental-footprints.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                <form action="{{ route('admin.environmental-footprints.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun empreinte" message="Commencez par créer une entrée." />
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