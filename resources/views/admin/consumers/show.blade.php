@extends('layouts.backend')

@section('title', 'Détail consommateur')
@section('page-title', 'Détail consommateur')

@section('content')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.consumers.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.consumers.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.consumers.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Utilisateur</dt><dd>{{ $item->user?->name ?? '—' }}</dd></div>
<div><dt>Niveau</dt><dd>{{ $item->sustainability_level ?? '—' }}</dd></div>
<div><dt>Budget</dt><dd>{{ $item->budget_range ?? '—' }}</dd></div>
<div><dt>Préférences</dt><dd>{{ collect($item->preferences ?? [])->map(fn ($p) => \App\Models\Consumer::PREFERENCES[$p] ?? $p)->implode(', ') ?: '—' }}</dd></div>
<div><dt>Allergies</dt><dd>{{ implode(', ', $item->allergies ?? []) ?: '—' }}</dd></div>
<div><dt>Restrictions</dt><dd>{{ implode(', ', $item->dietary_restrictions ?? []) ?: '—' }}</dd></div>

        </dl>
    </div>

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Recommandations calculées ({{ $item->personalRatings->count() }})</h3>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse($item->personalRatings->take(10) as $rating)
                <li class="py-2 flex justify-between gap-4">
                    <span>{{ $rating->product?->name }} <span class="text-slate-600">— {{ $rating->recommendation_reason }}</span></span>
                    <span class="font-semibold">{{ $rating->personalized_score }}/100</span>
                </li>
            @empty
                <li class="py-2 text-slate-600">Aucune recommandation.</li>
            @endforelse
        </ul>
    </div>
@endsection