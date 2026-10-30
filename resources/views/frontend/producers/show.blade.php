@extends('layouts.frontend')

@section('title', $producer->company_name)

@section('content')
    <x-page-header eyebrow="Producteur" :title="$producer->company_name" :subtitle="$producer->location">
        <a href="{{ route('producers.index') }}" class="tv-btn-secondary mb-6">← Retour</a>
    </x-page-header>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="tv-card">
            <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs uppercase tracking-wide text-slate-600">Localisation</dt><dd class="mt-1 font-semibold text-slate-800">{{ $producer->location ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-600">Méthode</dt><dd class="mt-1 font-semibold text-slate-800">{{ $producer->farming_method ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-600">Capacité</dt><dd class="mt-1 font-semibold text-slate-800">{{ $producer->production_capacity ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-600">Certifications</dt><dd class="mt-1 font-semibold text-slate-800">{{ implode(', ', $producer->certifications ?? []) ?: '—' }}</dd></div>
            </dl>
        </div>

        <h2 class="mt-10 mb-6 font-display text-2xl font-semibold text-brand-900">Produits associés</h2>
        @if($producer->products->isEmpty())
            <x-empty-state title="Aucun produit" message="Aucun produit publié pour le moment." />
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($producer->products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
