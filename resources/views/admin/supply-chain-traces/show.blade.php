@extends('layouts.backend')

@section('title', 'Détail trace')
@section('page-title', 'Détail de la trace logistique')

@section('content')
    @inject('ml', 'App\Services\Ml\MlService')
    <div class="flex gap-3 mb-6">
        @can('update', $item)
            <a href="{{ route('admin.supply-chain-traces.edit', $item) }}" class="tv-btn-primary">Modifier</a>
        @endcan
        <a href="{{ route('admin.supply-chain-traces.index') }}" class="tv-btn-secondary">Retour</a>
        @can('delete', $item)
            <form action="{{ route('admin.supply-chain-traces.destroy', $item) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
                @csrf @method('DELETE')
                <button class="tv-btn-danger" type="submit">Supprimer</button>
            </form>
        @endcan
    </div>

    <div class="tv-card">
        <dl class="tv-dl">
            <div><dt>Produit</dt><dd>{{ $item->product?->name ?? '—' }}</dd></div>
            <div><dt>Distributeur</dt><dd>{{ $item->distributor?->company_name ?? '—' }}</dd></div>
            <div><dt>Étape actuelle</dt><dd>{{ $item->current_stage ?? '—' }}</dd></div>
            <div><dt>Statut</dt><dd><x-status-badge :status="$item->status" /></dd></div>
            <div><dt>Position actuelle</dt><dd>{{ $item->current_location_lat !== null ? $item->current_location_lat.', '.$item->current_location_lon : '—' }}</dd></div>
            <div><dt>Distance totale</dt><dd>{{ $item->total_distance_km !== null ? $item->total_distance_km.' km' : '—' }}</dd></div>
        </dl>
    </div>

    @if($item->status === 'in_transit')
        @php($risk = $ml->delayRisk($item))
        <x-ml-card module="M3" title="Risque de retard" :unavailable="$risk === null">
            @if($risk)
                @php($color = ['faible' => 'bg-emerald-500', 'moyen' => 'bg-amber-400', 'élevé' => 'bg-red-500'][$risk['level']])
                <div class="flex items-center gap-4">
                    <p class="text-3xl font-semibold text-slate-800">{{ $risk['risk'] }} %</p>
                    <div class="h-3 flex-1 rounded-full bg-slate-100"><div class="h-3 rounded-full {{ $color }}" style="width: {{ $risk['risk'] }}%"></div></div>
                    <span class="text-sm font-medium">Risque {{ $risk['level'] }}</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">Gradient Boosting entraîné sur 4 000 livraisons : distance, nombre d’étapes, étape actuelle, type de distributeur, catégorie (périssable ou non), jours en transit et mois.</p>
            @endif
        </x-ml-card>
    @endif

    <div class="tv-card mt-6">
        <h3 class="tv-section-title">Parcours</h3>
        @if(empty($item->path_history))
            <p class="text-sm text-slate-600">Aucune étape enregistrée.</p>
        @else
            <ol class="relative ml-3 border-l-2 border-brand-200">
                @foreach($item->path_history as $step)
                    <li class="mb-5 ml-5">
                        <span class="absolute -left-[9px] mt-1 h-4 w-4 rounded-full border-2 border-white bg-brand-600"></span>
                        <p class="text-sm font-semibold text-slate-800">{{ ucfirst($step['stage'] ?? 'étape') }}</p>
                        <p class="text-sm text-slate-600">
                            {{ $step['location'] ?? 'Lieu non précisé' }}
                            @if(! empty($step['at'])) · {{ \Illuminate\Support\Carbon::parse($step['at'])->format('d/m/Y H:i') }} @endif
                            @if(isset($step['lat'], $step['lon'])) · <span class="text-slate-400">{{ $step['lat'] }}, {{ $step['lon'] }}</span> @endif
                        </p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
@endsection
