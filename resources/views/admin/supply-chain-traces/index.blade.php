@extends('layouts.backend')

@section('title', 'Traces logistiques')
@section('page-title', 'Traces logistiques')

@section('content')
    @inject('ml', 'App\Services\Ml\MlService')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
            
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        @can('create', \App\Models\SupplyChainTrace::class)
            <a href="{{ route('admin.supply-chain-traces.create') }}" class="tv-btn-primary">Ajouter</a>
        @endcan
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Produit</th><th>Étape</th><th>Statut</th><th>Distance (km)</th><th>Risque de retard (ML)</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? '—' }}</td><td>{{ $item->current_stage ?? '—' }}</td><td><x-status-badge :status="$item->status ?? '—'" /></td><td>{{ $item->total_distance_km ?? '—' }}</td>
                            <td>
                                @php($risk = $item->status === 'in_transit' ? $ml->delayRisk($item) : null)
                                @if($item->status === 'delayed') <x-ml-badge level="bad">Retard constaté</x-ml-badge>
                                @elseif($risk) <x-ml-badge :level="['faible' => 'good', 'moyen' => 'warn', 'élevé' => 'bad'][$risk['level']]" title="Gradient Boosting (M3)">{{ $risk['risk'] }} % · {{ $risk['level'] }}</x-ml-badge>
                                @else <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.supply-chain-traces.show', $item) }}" class="tv-link">Voir</a>
                                @can('update', $item)
                                <a href="{{ route('admin.supply-chain-traces.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                @endcan
                                @can('delete', $item)
                                <form action="{{ route('admin.supply-chain-traces.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun trace" message="Commencez par créer une entrée." />
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