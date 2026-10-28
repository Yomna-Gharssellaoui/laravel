@extends('layouts.backend')

@section('title', 'Détail produit')
@section('page-title', 'Détail produit')

@section('content')
    @php
        $cascade = collect([
            'certification(s)' => $item->certificates_count,
            'avis' => $item->reviews_count,
            'alerte(s)' => $item->alerts_count,
            'analyse(s) IA' => $item->ai_analyses_count,
            'trace(s) logistique(s)' => $item->supply_chain_traces_count,
            'évaluation(s) personnalisée(s)' => $item->personal_ratings_count,
        ])->filter()->map(fn ($n, $label) => "$n $label")->implode(', ');
        $confirm = 'Supprimer « '.$item->name.' » ?'.($cascade ? ' Seront aussi supprimés : '.$cascade.($item->environmentalFootprint ? ', son empreinte' : '').'.' : '');
    @endphp

    <div class="flex flex-wrap gap-3 mb-6">
        @can('update', $item)
            <a href="{{ route('admin.products.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        @endcan
        <a href="{{ route('admin.products.index') }}" class="tv-btn-secondary">Retour</a>
        @if(auth()->user()->isAdmin())
            <form action="{{ route('admin.products.analyze', $item) }}" method="POST">
                @csrf
                <button class="tv-btn-secondary" type="submit">Analyser (IA)</button>
            </form>
        @endif
        @if($item->status === 'published')
            <a href="{{ route('products.trace', $item) }}" class="tv-btn-secondary" target="_blank">Page publique de traçabilité</a>
        @endif
        @can('delete', $item)
            <form action="{{ route('admin.products.destroy', $item) }}" method="POST" onsubmit="return confirm(@js($confirm));">
                @csrf @method('DELETE')
                <button class="tv-btn-danger" type="submit">Supprimer</button>
            </form>
        @endcan
    </div>

    @if($item->status !== 'published' && ($missing = $item->missingForPublication()))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Pour publier ce produit, il manque : {{ implode(', ', $missing) }}.
        </div>
    @endif

    <div class="tv-card">
        <div class="flex flex-col gap-6 md:flex-row">
            @if($item->image_path)
                <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="h-40 w-40 rounded-xl object-cover border border-slate-200">
            @endif
            <dl class="tv-dl flex-1">
                <div><dt>Nom</dt><dd>{{ $item->name }}</dd></div>
                <div><dt>Code-barres</dt><dd>{{ $item->barcode ?? '—' }}</dd></div>
                <div><dt>SKU</dt><dd>{{ $item->sku ?? '—' }}</dd></div>
                <div><dt>Catégorie</dt><dd>{{ $item->category ?? '—' }}</dd></div>
                <div><dt>Origine</dt><dd>{{ $item->origin ?? '—' }}</dd></div>
                <div><dt>Statut</dt><dd><x-status-badge :status="$item->status" /></dd></div>
                <div><dt>Ingrédients</dt><dd>{{ $item->ingredients ? implode(' · ', array_map('trim', explode(',', $item->ingredients))) : '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Relations</h3>
        <dl class="tv-dl">
            <div><dt>Producteur</dt><dd>{{ $item->producer?->company_name ?? '—' }}</dd></div>
            <div><dt>Transformateur</dt><dd>{{ $item->transformer?->company_name ?? '—' }}</dd></div>
            <div><dt>Empreinte</dt><dd>
                @if($item->environmentalFootprint)
                    Score {{ $item->environmentalFootprint->ai_score ?? '—' }}/100 ({{ $item->environmentalFootprint->indicatorLevel() }})
                    @if(auth()->user()->isAdmin())
                        · <a class="tv-link" href="{{ route('admin.environmental-footprints.show', $item->environmentalFootprint) }}">Voir</a>
                    @endif
                @else — @endif
            </dd></div>
            <div><dt>Certifications</dt><dd>{{ $item->certificates_count }}</dd></div>
            <div><dt>Traces logistiques</dt><dd>{{ $item->supply_chain_traces_count }}</dd></div>
            <div><dt>Avis</dt><dd>{{ $item->reviews_count }}</dd></div>
            <div><dt>Alertes</dt><dd>{{ $item->alerts_count }}</dd></div>
        </dl>
    </div>

    @if($item->aiAnalyses->isNotEmpty())
        <div class="tv-card mt-6">
            <h3 class="tv-section-title">Dernières analyses IA</h3>
            <div class="space-y-3">
                @foreach($item->aiAnalyses->sortByDesc('created_at')->take(3) as $analysis)
                    <div class="rounded-lg border border-slate-200 p-3 text-sm">
                        <div class="flex justify-between gap-2">
                            <span class="font-medium">Greenwashing : {{ $analysis->greenwashing_score ?? '—' }}/100 · crédibilité {{ $analysis->credibility_rating ?? '—' }}</span>
                            <span class="text-slate-500">{{ $analysis->model_used }} · {{ $analysis->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="mt-1 text-slate-600">{{ $analysis->ai_summary }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
