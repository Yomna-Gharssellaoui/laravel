@extends('layouts.frontend')

@section('title', $transformer->company_name)

@section('content')
    <x-page-header eyebrow="Transformateur" :title="$transformer->company_name" :subtitle="$transformer->location">
        <a href="{{ route('transformers.index') }}" class="tv-btn-secondary mb-6">← Retour</a>
    </x-page-header>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="tv-card">
            <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Localisation</dt><dd class="mt-1 font-semibold text-slate-800">{{ $transformer->location ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Type de transformation</dt><dd class="mt-1 font-semibold text-slate-800">{{ $transformer->transformation_type ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Capacité</dt><dd class="mt-1 font-semibold text-slate-800">{{ $transformer->production_capacity ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Certifications</dt><dd class="mt-1 font-semibold text-slate-800">{{ implode(', ', $transformer->certifications ?? []) ?: '—' }}</dd></div>
            </dl>
        </div>

        <h2 class="mt-10 mb-6 font-display text-2xl font-semibold text-brand-900">Produits associés</h2>
        @if($transformer->products->isEmpty())
            <x-empty-state title="Aucun produit" message="Aucun produit publié pour le moment." />
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($transformer->products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
