@props(['producer'])

<a href="{{ route('producers.show', $producer) }}" class="group block h-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
    <div class="bg-gradient-to-br from-brand-800 via-brand-700 to-brand-600 p-5 text-white">
        <div class="flex items-center justify-between gap-3">
            <span class="inline-flex rounded-full bg-black/25 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white">Producteur</span>
            @if(!empty($producer->farming_method))
                <span class="rounded-full bg-black/25 px-2 py-1 text-xs font-medium text-white">{{ $producer->farming_method }}</span>
            @endif
        </div>
        <h3 class="mt-4 font-display text-2xl font-semibold text-white">{{ $producer->company_name }}</h3>
    </div>

    <div class="space-y-3 p-5">
        <p class="text-sm text-slate-600">{{ $producer->location ?? 'Localisation non renseignée' }}</p>
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">{{ $producer->products()->where('status', 'published')->count() }} produit(s)</span>
            <span class="font-medium text-brand-700">Voir le profil</span>
        </div>
    </div>
</a>