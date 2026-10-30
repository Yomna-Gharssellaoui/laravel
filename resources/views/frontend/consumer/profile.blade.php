@extends('layouts.frontend')

@section('title', 'Mon espace')

@section('content')
    <x-page-header eyebrow="Mon espace" :title="'Bonjour '.auth()->user()->name" subtitle="Vos préférences servent à calculer des recommandations de produits adaptées à vous." />

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap gap-3">
            <a href="{{ route('consumer.recommendations') }}" class="tv-btn-primary">Voir mes recommandations</a>
            <a href="{{ route('consumer.edit') }}" class="tv-btn-secondary">Modifier mes préférences</a>
            <a href="{{ route('profile.edit') }}" class="tv-btn-secondary">Mon compte (email, mot de passe)</a>
            @if(auth()->user()->role === 'consumer')
                @php $proRequest = auth()->user()->pendingProAccountRequest(); @endphp
                <a href="{{ route('pro-account.create') }}" class="tv-btn-secondary">
                    {{ $proRequest ? 'Ma demande de compte pro (en attente)' : 'Demander un compte professionnel' }}
                </a>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="tv-card">
                <h2 class="tv-section-title">Informations</h2>
                <dl class="space-y-4 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-600">Nom</dt><dd class="mt-1 font-semibold text-slate-800">{{ auth()->user()->name }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-600">Email</dt><dd class="mt-1 font-semibold text-slate-800">{{ auth()->user()->email }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-600">Niveau de durabilité</dt><dd class="mt-1 font-semibold text-slate-800">{{ $consumer->sustainability_level ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-600">Budget</dt><dd class="mt-1 font-semibold text-slate-800">{{ $consumer->budget_range ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="tv-card lg:col-span-2">
                <h2 class="tv-section-title">Préférences</h2>
                <div class="flex flex-wrap gap-2">
                    @forelse($consumer->preferences ?? [] as $pref)
                        <span class="rounded-full bg-brand-100 px-3 py-1 text-sm font-medium text-brand-800">{{ \App\Models\Consumer::PREFERENCES[$pref] ?? $pref }}</span>
                    @empty
                        <p class="text-sm text-slate-600">Aucune préférence renseignée pour le moment.</p>
                    @endforelse
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-600">Restrictions alimentaires</p>
                        <p class="mt-2 text-slate-800">{{ implode(', ', $consumer->dietary_restrictions ?? []) ?: 'Aucune' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-600">Allergies</p>
                        <p class="mt-2 text-slate-800">{{ implode(', ', $consumer->allergies ?? []) ?: 'Aucune' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
