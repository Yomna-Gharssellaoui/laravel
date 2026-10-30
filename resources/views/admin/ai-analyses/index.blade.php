@extends('layouts.backend')

@section('title', 'Analyses IA')
@section('page-title', 'Analyses IA')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="tv-input w-56">
            
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        <p class="text-sm {{ $aiEnabled ? 'text-emerald-700' : 'text-amber-800' }}">
            {{ $aiEnabled ? 'Analyse par IA active ('.$modelName.').' : 'Mode heuristique : ajoutez GEMINI_API_KEY ou ANTHROPIC_API_KEY dans .env pour activer l’IA.' }}
            Lancez une analyse depuis une déclaration ou un produit.
        </p>
    </div>


    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Produit</th><th>Type</th><th>Risque greenwashing</th><th>Crédibilité</th><th>Moteur</th><th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? '—' }}</td><td>{{ $item->analysis_type ?? '—' }}</td><td>{{ $item->greenwashing_score ?? '—' }}/100</td><td>{{ $item->credibility_rating ?? '—' }}</td><td>{{ $item->is_demo ? 'heuristique' : 'IA' }}</td><td>{{ $item->created_at?->format('d/m/Y') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.ai-analyses.show', $item) }}" class="tv-link">Voir</a>
                                <form action="{{ route('admin.ai-analyses.destroy', $item) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Confirmer la suppression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucune analyse" message="Cliquez sur « Relancer l’analyse IA » depuis une déclaration environnementale." />
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