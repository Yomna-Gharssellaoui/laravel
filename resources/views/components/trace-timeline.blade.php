@inject('ml', 'App\Services\Ml\MlService')
@if($traces->isEmpty())
    <x-empty-state title="Aucune trace" message="Le parcours de ce produit n’a pas encore été renseigné." />
@else
    <div class="space-y-8">
        @foreach($traces as $trace)
            <div>
                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
                    <span class="font-semibold text-slate-800">{{ $trace->distributor?->company_name ?? 'Distributeur non renseigné' }}</span>
                    <x-status-badge :status="$trace->status" />
                    @if($trace->total_distance_km !== null)
                        <span class="text-slate-500">{{ $trace->total_distance_km }} km parcourus</span>
                    @endif
                    @if($showRisk && ($risk = $ml->delayRisk($trace)))
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ ['faible' => 'bg-emerald-100 text-emerald-800', 'moyen' => 'bg-amber-100 text-amber-800', 'élevé' => 'bg-red-100 text-red-800'][$risk['level']] ?? 'bg-slate-100 text-slate-700' }}"
                              title="Prédiction d’un modèle de machine learning entraîné sur l’historique de livraisons">
                            Risque de retard (IA) : {{ $risk['level'] }} · {{ number_format($risk['risk'], 0) }} %
                        </span>
                    @endif
                </div>
                <ol class="relative ml-3 border-l-2 border-brand-200">
                    @forelse($trace->path_history ?? [] as $step)
                        <li class="mb-5 ml-6">
                            <span class="absolute -left-[9px] mt-1 h-4 w-4 rounded-full border-2 border-white {{ $loop->last ? 'bg-brand-600' : 'bg-brand-300' }}"></span>
                            <p class="font-semibold text-slate-800">{{ ucfirst($step['stage'] ?? 'Étape') }}</p>
                            <p class="text-sm text-slate-600">
                                {{ $step['location'] ?? 'Lieu non précisé' }}
                                @if(! empty($step['at'])) · {{ \Illuminate\Support\Carbon::parse($step['at'])->format('d/m/Y') }} @endif
                            </p>
                        </li>
                    @empty
                        <li class="ml-6 text-sm text-slate-600">Étape actuelle : {{ $trace->current_stage ?? '—' }}</li>
                    @endforelse
                </ol>
            </div>
        @endforeach
    </div>
@endif
