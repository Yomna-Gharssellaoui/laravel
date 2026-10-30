@extends('layouts.backend')

@section('title', 'Détail de la déclaration')
@section('page-title', 'Détail de la déclaration')

@section('content')
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('admin.environmental-claims.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.environmental-claims.index') }}" class="tv-btn-secondary">Retour</a>
        @if($item->product_id)
            <form action="{{ route('admin.environmental-claims.analyze', $item) }}" method="POST">
                @csrf
                <button class="tv-btn-secondary" type="submit">Relancer l’analyse IA</button>
            </form>
        @endif
        <a href="{{ route('admin.compliance-checks.create', ['environmental_claim_id' => $item->id]) }}" class="tv-btn-secondary">Ajouter une vérification</a>
        <form action="{{ route('admin.environmental-claims.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer cette déclaration et ses vérifications ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
            <div><dt>Type</dt><dd>{{ $item->claim_type ?? '—' }}</dd></div>
            <div><dt>Titre</dt><dd>{{ $item->title ?? '—' }}</dd></div>
            <div><dt>Statut</dt><dd><x-status-badge :status="$item->status" /></dd></div>
            <div><dt>Score de confiance</dt><dd>{{ $item->confidence_score !== null ? $item->confidence_score.'/100' : 'En attente d’analyse' }}</dd></div>
            <div><dt>Document</dt><dd>
                @if($item->document_path)
                    <a href="{{ $item->documentUrl() }}" target="_blank" class="tv-link">Ouvrir le justificatif</a>
                @else
                    {{ $item->source_document ?? '—' }}
                @endif
            </dd></div>
            <div class="sm:col-span-2"><dt>Description</dt><dd>{{ $item->description ?? '—' }}</dd></div>
        </dl>
    </div>

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Analyse de greenwashing</h3>
        @if($analysis = $item->aiAnalyses->first())
            <div class="text-sm space-y-2">
                <p><span class="font-semibold">Risque : {{ $analysis->greenwashing_score }}/100</span> · crédibilité {{ $analysis->credibility_rating }} · <span class="text-slate-600">{{ $analysis->model_used }}, {{ $analysis->created_at->diffForHumans() }}</span></p>
                <p class="text-slate-700">{{ $analysis->ai_summary }}</p>
                @if(! empty($analysis->red_flags))
                    <ul class="list-disc pl-5 text-red-700">
                        @foreach($analysis->red_flags as $flag)
                            <li>{{ $flag }}</li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('admin.ai-analyses.show', $analysis) }}" class="tv-link">Détail de l’analyse</a>
            </div>
        @else
            <p class="text-sm text-slate-600">Aucune analyse pour l’instant (elle tourne en tâche de fond : lancez <code>php artisan queue:work</code>, ou cliquez sur « Relancer l’analyse IA »).</p>
        @endif
    </div>

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Vérifications associées</h3>
        <ul class="list-disc pl-5 text-sm text-slate-700">
            @forelse($item->complianceChecks as $check)
                <li><a class="tv-link" href="{{ route('admin.compliance-checks.show', $check) }}">{{ $check->check_type }}</a> — {{ $check->status }}</li>
            @empty
                <li>Aucune vérification créée pour cette déclaration.</li>
            @endforelse
        </ul>
    </div>
@endsection
