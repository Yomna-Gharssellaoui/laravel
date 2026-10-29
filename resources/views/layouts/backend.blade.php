<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Back-office') — Trace Verte</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|fraunces:500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        <aside class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-slate-200 bg-white transition lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white font-display">TV</span>
                <div>
                    <p class="font-display text-lg font-semibold text-brand-800">Trace Verte</p>
                    <p class="text-xs text-slate-500">Back-office</p>
                </div>
            </div>
            <nav class="space-y-1 p-4 text-sm overflow-y-auto max-h-[calc(100vh-4rem)]">
                <a href="{{ route('admin.dashboard') }}" class="tv-nav-link {{ request()->routeIs('admin.dashboard') ? 'tv-nav-link-active' : '' }}">Tableau de bord</a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Produits</p>
                <a href="{{ route('admin.products.index') }}" class="tv-nav-link {{ request()->routeIs('admin.products.*') ? 'tv-nav-link-active' : '' }}">{{ auth()->user()->isAdmin() ? 'Produits' : 'Mes produits' }}</a>
                <a href="{{ route('admin.supply-chain-traces.index') }}" class="tv-nav-link {{ request()->routeIs('admin.supply-chain-traces.*') ? 'tv-nav-link-active' : '' }}">Traces logistiques</a>
                <a href="{{ route('admin.certificates.index') }}" class="tv-nav-link {{ request()->routeIs('admin.certificates.*') ? 'tv-nav-link-active' : '' }}">Certifications</a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.environmental-footprints.index') }}" class="tv-nav-link {{ request()->routeIs('admin.environmental-footprints.*') ? 'tv-nav-link-active' : '' }}">Empreintes</a>
                <a href="{{ route('admin.environmental-claims.index') }}" class="tv-nav-link {{ request()->routeIs('admin.environmental-claims.*') ? 'tv-nav-link-active' : '' }}">Déclarations</a>
                <a href="{{ route('admin.compliance-checks.index') }}" class="tv-nav-link {{ request()->routeIs('admin.compliance-checks.*') ? 'tv-nav-link-active' : '' }}">Conformité</a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Acteurs</p>
                <a href="{{ route('admin.producers.index') }}" class="tv-nav-link {{ request()->routeIs('admin.producers.*') ? 'tv-nav-link-active' : '' }}">Producteurs</a>
                <a href="{{ route('admin.transformers.index') }}" class="tv-nav-link {{ request()->routeIs('admin.transformers.*') ? 'tv-nav-link-active' : '' }}">Transformateurs</a>
                <a href="{{ route('admin.distributors.index') }}" class="tv-nav-link {{ request()->routeIs('admin.distributors.*') ? 'tv-nav-link-active' : '' }}">Distributeurs</a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Qualité</p>
                <a href="{{ route('admin.alerts.index') }}" class="tv-nav-link {{ request()->routeIs('admin.alerts.*') ? 'tv-nav-link-active' : '' }}">Alertes</a>
                <a href="{{ route('admin.ai-analyses.index') }}" class="tv-nav-link {{ request()->routeIs('admin.ai-analyses.*') ? 'tv-nav-link-active' : '' }}">Analyses IA</a>
                <a href="{{ route('admin.reviews.index') }}" class="tv-nav-link {{ request()->routeIs('admin.reviews.*') ? 'tv-nav-link-active' : '' }}">Modération des avis</a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Consommateurs</p>
                <a href="{{ route('admin.consumers.index') }}" class="tv-nav-link {{ request()->routeIs('admin.consumers.*') ? 'tv-nav-link-active' : '' }}">Profils</a>
                <a href="{{ route('admin.personal-ratings.index') }}" class="tv-nav-link {{ request()->routeIs('admin.personal-ratings.*') ? 'tv-nav-link-active' : '' }}">Recommandations</a>
                <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administration</p>
                <a href="{{ route('admin.users.index') }}" class="tv-nav-link {{ request()->routeIs('admin.users.*') ? 'tv-nav-link-active' : '' }}">Utilisateurs</a>
                @endif
                <div class="pt-4 mt-4 border-t border-slate-100">
                    <a href="{{ route('home') }}" class="tv-nav-link">← Front office</a>
                </div>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" class="tv-btn-secondary lg:hidden" @click="sidebarOpen = !sidebarOpen">Menu</button>
                    <h1 class="font-display text-xl font-semibold text-brand-800">@yield('page-title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 text-slate-600 hover:text-brand-700">
                        <x-avatar :user="auth()->user()" class="h-8 w-8 text-xs" />
                        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    </a>
                    <x-status-badge :status="auth()->user()->role" />
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="tv-btn-secondary" type="submit">Déconnexion</button></form>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <x-flash-messages />
                @yield('content')
            </main>
        </div>
    </div>

    <div class="fixed inset-0 z-40 bg-black/40 lg:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>
</body>
</html>
