@extends('layouts.frontend')

@section('title', 'Traçabilité de '.$product->name)

@section('content')
    <x-page-header eyebrow="Traçabilité" :title="$product->name" :subtitle="'Producteur : '.($product->producer?->company_name ?? '—').($product->transformer ? ' · Transformateur : '.$product->transformer->company_name : '')">
        <a href="{{ route('products.show', $product) }}" class="tv-btn-secondary mb-6">← Retour au produit</a>
    </x-page-header>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div class="tv-card lg:col-span-2">
            <h2 class="tv-section-title">Parcours du produit</h2>
            <x-trace-timeline :traces="$product->supplyChainTraces" :show-risk="true" />
        </div>
        <div class="tv-card">
            <h2 class="tv-section-title">Certifications</h2>
            <ul class="space-y-3 text-sm">
                @forelse($product->certificates as $certificate)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-slate-700">{{ $certificate->certificate_type }}</span>
                        <x-status-badge :status="$certificate->status" />
                    </li>
                @empty
                    <li class="text-slate-600">Aucune certification.</li>
                @endforelse
            </ul>
        </div>
    </section>
@endsection
