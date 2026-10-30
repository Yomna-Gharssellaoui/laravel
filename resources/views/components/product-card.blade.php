@props(['product'])

<a href="{{ route('products.show', $product) }}" class="group block h-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-soft transition duration-200 hover:-translate-y-1 hover:shadow-xl">
    <div class="relative h-32 bg-gradient-to-br from-brand-700 via-brand-500 to-brand-300">
        <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 20% 20%, white 0, transparent 40%), radial-gradient(circle at 80% 0%, #dcefe3 0, transparent 35%);"></div>
        <div class="absolute inset-x-4 bottom-4 flex items-end justify-between">
            <span class="rounded-md bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-800">{{ $product->category ?? 'Produit' }}</span>
            @if($product->environmentalFootprint?->ai_score !== null)
                <x-footprint-score :score="$product->environmentalFootprint->ai_score" />
            @endif
        </div>
    </div>

    <div class="p-4">
        <h3 class="font-display text-xl font-semibold text-brand-900 group-hover:text-brand-700">{{ $product->name }}</h3>
        <p class="mt-2 text-sm text-slate-600">{{ $product->origin ?? 'Origine non renseignée' }}</p>

        @if($product->producer)
            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-600">Par {{ $product->producer->company_name }}</p>
        @endif

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-600">
            <span>{{ $product->status ?? 'Publiée' }}</span>
            <span class="font-semibold text-brand-700">Voir le produit</span>
        </div>
    </div>
</a>
