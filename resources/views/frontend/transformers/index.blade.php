@extends('layouts.frontend')

@section('title', 'Transformateurs')

@section('content')
    <x-page-header eyebrow="Acteurs" title="Transformateurs" subtitle="Les structures qui transforment les matières premières et assurent la qualité du produit fini." />

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if($transformers->isEmpty())
            <x-empty-state title="Aucun transformateur" message="Les transformateurs apparaîtront ici dès leur inscription." />
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($transformers as $transformer)
                    <a href="{{ route('transformers.show', $transformer) }}" class="group block h-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="bg-gradient-to-br from-brand-800 to-brand-500 p-5 text-white">
                            <span class="inline-flex rounded-full bg-black/25 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white">Transformation</span>
                            <h2 class="mt-4 font-display text-2xl font-semibold">{{ $transformer->company_name }}</h2>
                        </div>
                        <div class="space-y-3 p-5">
                            <p class="text-sm text-slate-600">{{ $transformer->location ?? 'Localisation non renseignée' }}</p>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">{{ $transformer->products_count ?? 0 }} produit(s)</span>
                                <span class="font-medium text-brand-700">Voir le profil</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-10">{{ $transformers->links() }}</div>
        @endif
    </section>
@endsection
