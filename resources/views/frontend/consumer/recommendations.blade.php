@extends('layouts.frontend')

@section('title', 'Mes recommandations')

@section('content')
    <x-page-header eyebrow="Recommandations" title="Produits adaptés à votre profil"
        :subtitle="'Calculées à partir de vos préférences ('.(collect($consumer->preferences ?? [])->map(fn ($p) => \App\Models\Consumer::PREFERENCES[$p] ?? $p)->implode(', ') ?: 'aucune').'), de vos allergies et des preuves de chaque produit.'" />

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('consumer.edit') }}" class="tv-btn-secondary">Modifier mes préférences</a>
        </div>

        @if($recommended->isEmpty())
            <x-empty-state title="Aucune recommandation" message="Aucun produit publié ne correspond pour l’instant à votre profil." />
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($recommended as $rating)
                    @php($product = $rating->product)
                    <div class="tv-card flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-display text-xl font-semibold text-brand-900">{{ $product->name }}</h2>
                                <p class="text-sm text-slate-500">{{ $product->producer?->company_name ?? 'Producteur inconnu' }} · {{ $product->origin ?? '—' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-sm font-bold {{ $rating->personalized_score >= 70 ? 'bg-brand-600 text-white' : ($rating->personalized_score >= 40 ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700') }}">{{ $rating->personalized_score }}/100</span>
                        </div>
                        <p class="mt-3 flex-1 text-sm text-slate-600">{{ $rating->recommendation_reason }}</p>
                        <a href="{{ route('products.show', $product) }}" class="tv-btn-primary mt-5 self-start">Voir le produit</a>
                    </div>
                @endforeach
            </div>
        @endif

        @if($excluded->isNotEmpty())
            <div class="tv-card mt-10">
                <h2 class="tv-section-title">Produits écartés</h2>
                <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
                    @foreach($excluded as $rating)
                        <li>{{ $rating->product->name }} — {{ $rating->recommendation_reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
@endsection
