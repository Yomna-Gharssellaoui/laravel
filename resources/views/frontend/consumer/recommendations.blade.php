@extends('layouts.frontend')

@section('title', 'Mes recommandations')

@section('content')
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2 text-uppercase fw-semibold">Recommandations</span>
                    <h1 class="mt-4 display-5 fw-bold text-dark mb-2">Produits adaptés à votre profil</h1>
                    <p class="text-secondary mb-0">
                        Calculées à partir de vos préférences
                        ({{ collect($consumer->preferences ?? [])->map(fn ($p) => \App\Models\Consumer::PREFERENCES[$p] ?? $p)->implode(', ') ?: 'aucune' }}),
                        de vos allergies et des preuves de chaque produit (empreinte, certifications, alertes, avis).
                    </p>
                </div>
                <a href="{{ route('consumer.edit') }}" class="btn btn-outline-secondary btn-lg">Modifier mes préférences</a>
            </div>

            @if($recommended->isEmpty())
                <x-empty-state title="Aucune recommandation" message="Aucun produit publié ne correspond pour l’instant à votre profil." />
            @else
                <div class="row g-4">
                    @foreach($recommended as $rating)
                        @php($product = $rating->product)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <h2 class="h5 fw-bold text-dark mb-1">{{ $product->name }}</h2>
                                            <small class="text-muted">{{ $product->producer?->company_name ?? 'Producteur inconnu' }} · {{ $product->origin ?? '—' }}</small>
                                        </div>
                                        <span class="badge rounded-pill {{ $rating->personalized_score >= 70 ? 'bg-success' : ($rating->personalized_score >= 40 ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6">{{ $rating->personalized_score }}</span>
                                    </div>
                                    <p class="mt-3 mb-4 text-secondary flex-grow-1">{{ $rating->recommendation_reason }}</p>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-success">Voir le produit</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($excluded->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4 mt-5">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark">Produits écartés</h2>
                        <ul class="mb-0 text-secondary">
                            @foreach($excluded as $rating)
                                <li>{{ $rating->product->name }} — {{ $rating->recommendation_reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
