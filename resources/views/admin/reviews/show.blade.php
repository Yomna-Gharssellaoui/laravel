@extends('layouts.backend')

@section('title', 'Détail avis')
@section('page-title', 'Modération de l’avis')

@section('content')
    @inject('ml', 'App\Services\Ml\MlService')
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('admin.reviews.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.reviews.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
            <div><dt>Auteur</dt><dd>{{ $item->user?->name ?? 'Compte supprimé' }}</dd></div>
            <div><dt>Note</dt><dd>{{ $item->rating }}/5</dd></div>
            <div><dt>Statut</dt><dd><x-status-badge :status="$item->status" /></dd></div>
            <div><dt>Date</dt><dd>{{ $item->created_at?->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Titre</dt><dd>{{ $item->title ?? '—' }}</dd></div>
            <div class="sm:col-span-2"><dt>Commentaire</dt><dd>{{ $item->comment ?? '—' }}</dd></div>
        </dl>
    </div>

    @php($analysis = $ml->reviewAnalysis($item))
    <x-ml-card module="M4" title="Analyse automatique de l’avis" :unavailable="$analysis === null">
        @if($analysis)
            <div class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <p class="font-semibold">Sentiment : {{ $analysis['sentiment'] }} ({{ $analysis['confidence'] }} %)</p>
                    @foreach($analysis['probabilities'] as $label => $value)
                        <div class="mt-1 flex items-center gap-2"><span class="w-16 text-slate-500">{{ $label }}</span><div class="h-2 flex-1 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-violet-500" style="width: {{ $value }}%"></div></div><span class="w-12 text-right">{{ $value }} %</span></div>
                    @endforeach
                </div>
                <div>
                    <p class="font-semibold {{ $analysis['is_spam'] ? 'text-red-700' : 'text-emerald-700' }}">{{ $analysis['is_spam'] ? 'Spam probable' : 'Pas de spam détecté' }} ({{ $analysis['spam_probability'] }} %)</p>
                    <p class="mt-1 text-xs text-slate-500">TF-IDF + régression logistique, entraînés sur 6 000 avis. Les liens et numéros de téléphone sont reconnus comme indices de spam.</p>
                    @if($item->rating >= 4 && $analysis['sentiment'] === 'négatif' || $item->rating <= 2 && $analysis['sentiment'] === 'positif')
                        <p class="mt-2 text-xs font-semibold text-amber-700">La note ({{ $item->rating }}/5) contredit le texte : à vérifier.</p>
                    @endif
                </div>
            </div>
        @endif
    </x-ml-card>

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Décision</h3>
        <form action="{{ route('admin.reviews.moderate', $item) }}" method="POST" class="flex flex-wrap items-end gap-4">
            @csrf @method('PATCH')
            <div>
                <x-input-label for="status" value="Statut" />
                <select name="status" id="status" class="tv-input mt-1">
                    @foreach(\App\Models\Review::STATUSES as $st)
                        <option value="{{ $st }}" @selected($item->status === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
                <input type="hidden" name="verified_purchase" value="0">
                <input type="checkbox" name="verified_purchase" value="1" class="rounded border-slate-300" @checked($item->verified_purchase)> Achat vérifié
            </label>
            <button type="submit" class="tv-btn-primary">Enregistrer la décision</button>
        </form>
    </div>
@endsection
