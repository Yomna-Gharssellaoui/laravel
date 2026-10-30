@extends('layouts.backend')

@section('title', 'Détail analyse')
@section('page-title', 'Analyse de greenwashing')

@section('content')
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('admin.ai-analyses.index') }}" class="tv-btn-secondary">Retour</a>
        @if($item->environmentalClaim)
            <form action="{{ route('admin.environmental-claims.analyze', $item->environmentalClaim) }}" method="POST">
                @csrf
                <button class="tv-btn-secondary" type="submit">Relancer</button>
            </form>
        @elseif($item->product)
            <form action="{{ route('admin.products.analyze', $item->product) }}" method="POST">
                @csrf
                <button class="tv-btn-secondary" type="submit">Relancer</button>
            </form>
        @endif
        <form action="{{ route('admin.ai-analyses.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Produit</dt><dd>@if($item->product)<a class="tv-link" href="{{ route('admin.products.show', $item->product) }}">{{ $item->product->name }}</a>@else — @endif</dd></div>
            <div><dt>Déclaration</dt><dd>@if($item->environmentalClaim)<a class="tv-link" href="{{ route('admin.environmental-claims.show', $item->environmentalClaim) }}">{{ $item->environmentalClaim->title }}</a>@else Toutes les déclarations du produit @endif</dd></div>
            <div><dt>Risque de greenwashing</dt><dd>
                <span class="font-semibold {{ $item->greenwashing_score >= 65 ? 'text-red-700' : ($item->greenwashing_score >= 35 ? 'text-amber-800' : 'text-emerald-700') }}">{{ $item->greenwashing_score }}/100</span>
            </dd></div>
            <div><dt>Crédibilité</dt><dd>{{ $item->credibility_rating ?? '—' }}</dd></div>
            <div><dt>Moteur</dt><dd>{{ $item->model_used }} {{ $item->is_demo ? '(heuristique locale, sans IA)' : '' }}</dd></div>
            <div><dt>Date</dt><dd>{{ $item->created_at?->format('d/m/Y H:i') }}</dd></div>
            <div class="sm:col-span-2"><dt>Résumé</dt><dd>{{ $item->ai_summary ?? '—' }}</dd></div>
        </dl>
    </div>

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Signaux d’alerte</h3>
        <ul class="list-disc pl-5 text-sm text-slate-700">
            @forelse($item->red_flags ?? [] as $flag)
                <li>{{ $flag }}</li>
            @empty
                <li>Aucun signal d’alerte relevé.</li>
            @endforelse
        </ul>
        <p class="mt-4 text-xs text-slate-600">Aide à la décision : ce score ne constitue pas une preuve de fraude.</p>
    </div>
@endsection
