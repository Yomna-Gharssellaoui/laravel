@extends('layouts.backend')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
    @php
        $labels = [
            'products' => 'Produits', 'published_products' => 'Publiés', 'open_alerts' => 'Alertes ouvertes',
            'certificates' => 'Certifications', 'expiring_certificates' => 'Certif. expirant < 30 j',
            'traces_in_transit' => 'Traces en transit', 'users' => 'Utilisateurs', 'consumers' => 'Consommateurs',
            'pending_reviews' => 'Avis à modérer', 'analyses' => 'Analyses IA',
        ];
        $barColors = ['bg-brand-600', 'bg-emerald-400', 'bg-amber-400', 'bg-red-500'];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($stats as $key => $value)
            <div class="tv-card py-4">
                <p class="text-xs uppercase tracking-wide text-slate-600">{{ $labels[$key] ?? $key }}</p>
                <p class="mt-1 text-2xl font-semibold text-brand-800">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @foreach($charts as $title => $data)
            @php($max = max(1, max($data ?: [0])))
            <div class="tv-card">
                <h3 class="tv-section-title">{{ $title }}</h3>
                <div class="space-y-2">
                    @foreach($data as $label => $count)
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-28 shrink-0 text-slate-600">{{ $label }}</span>
                            <div class="h-3 flex-1 rounded-full bg-slate-100" role="img" aria-label="{{ $label }} : {{ $count }}">
                                <div class="h-3 rounded-full {{ $barColors[$loop->index % count($barColors)] }}" style="width: {{ round($count / $max * 100) }}%"></div>
                            </div>
                            <span class="w-8 text-right font-medium text-slate-700">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="tv-card">
            <div class="flex items-center justify-between">
                <h3 class="tv-section-title">Derniers produits</h3>
                <a href="{{ route('admin.products.index') }}" class="tv-link text-sm">Tous</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($recentProducts as $p)
                    <li class="flex justify-between gap-3 py-2">
                        <a href="{{ route('admin.products.show', $p) }}" class="tv-link">{{ $p->name }}</a>
                        <span class="text-slate-600">{{ $p->producer?->company_name ?? '—' }} · <x-status-badge :status="$p->status" /></span>
                    </li>
                @empty
                    <li class="py-2 text-slate-600">Aucun produit.</li>
                @endforelse
            </ul>
        </div>
        <div class="tv-card">
            <div class="flex items-center justify-between">
                <h3 class="tv-section-title">Alertes ouvertes</h3>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.alerts.index') }}" class="tv-link text-sm">Toutes</a>
                @endif
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($recentAlerts as $a)
                    <li class="flex justify-between gap-3 py-2">
                        <span>{{ $a->title }} <span class="text-slate-600">— {{ $a->product?->name }}</span></span>
                        <x-status-badge :status="$a->severity" />
                    </li>
                @empty
                    <li class="py-2 text-slate-600">Aucune alerte ouverte.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
