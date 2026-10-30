@extends('layouts.backend')

@section('title', 'Détail producteur')
@section('page-title', 'Détail producteur')

@section('content')
    @inject('ml', 'App\Services\Ml\MlService')
    <div class="flex gap-3 mb-6">
        <a href="{{ route('admin.producers.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        <a href="{{ route('admin.producers.index') }}" class="tv-btn-secondary">Retour</a>
        <form action="{{ route('admin.producers.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
            @csrf @method('DELETE')
            <button class="tv-btn-danger" type="submit">Supprimer</button>
        </form>
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Entreprise</dt><dd>{{ $item->company_name ?? '—' }}</dd></div>
<div><dt>Lieu</dt><dd>{{ $item->location ?? '—' }}</dd></div>
<div><dt>Méthode</dt><dd>{{ $item->farming_method ?? '—' }}</dd></div>
<div><dt>Produits</dt><dd>{{ $item->products_count ?? '—' }}</dd></div>

        </dl>
    </div>
    
    @php($profile = $ml->producerProfile($item))
    <x-ml-card module="M2" title="Profil de producteur" :unavailable="$profile === null">
        @if($profile)
            <p class="text-lg font-semibold text-violet-800">{{ $profile['label'] }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $profile['description'] }}</p>
            <p class="mt-2 text-xs text-slate-600">Groupe trouvé par K-Means (4 profils) d’après le mode de culture, la capacité ({{ \App\Services\Ml\MlService::capacityInTonnes($item->production_capacity) }} t/an), {{ count($item->certifications ?? []) }} certification(s) et {{ count($item->crop_types ?? []) }} culture(s). Représentativité du profil : {{ $profile['typicality'] }} %.</p>
        @endif
    </x-ml-card>
@endsection
