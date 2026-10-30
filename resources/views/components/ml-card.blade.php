@props(['module', 'title', 'unavailable' => false])

<div {{ $attributes->merge(['class' => 'tv-card mt-6 border-l-4 border-violet-400']) }}>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h3 class="tv-section-title mb-0">{{ $title }}</h3>
        <span class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-800" title="Modèle scikit-learn entraîné localement (service ml/)">
            Modèle ML · {{ $module }}
        </span>
    </div>
    @if($unavailable)
        <p class="text-sm text-slate-600">Prédiction indisponible : le service ML n’est pas démarré (<code>cd ml</code> puis <code>uvicorn service:app --port 8001</code>).</p>
    @else
        {{ $slot }}
    @endif
</div>
