<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Trace Verte') — Traçabilité alimentaire</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|fraunces:500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--tv-surface)] font-sans antialiased text-slate-800">
    <header class="sticky top-0 z-40 border-b border-brand-100 bg-white/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('logo.png') }}" alt="" class="h-10 w-10 rounded-full border border-brand-100 object-cover">
                <span class="font-display text-xl font-semibold text-brand-800">Trace Verte</span>
            </a>

            <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 lg:hidden" data-menu-toggle aria-expanded="false" aria-controls="main-menu">Menu</button>

            <div id="main-menu" class="hidden w-full flex-col gap-3 lg:flex lg:w-auto lg:flex-row lg:items-center lg:gap-6" data-menu>
                <a href="{{ route('products.index') }}" class="text-sm font-medium {{ request()->routeIs('products.*') ? 'text-brand-700' : 'text-slate-600 hover:text-brand-700' }}">Produits</a>
                <a href="{{ route('producers.index') }}" class="text-sm font-medium {{ request()->routeIs('producers.*') ? 'text-brand-700' : 'text-slate-600 hover:text-brand-700' }}">Producteurs</a>
                <a href="{{ route('transformers.index') }}" class="text-sm font-medium {{ request()->routeIs('transformers.*') ? 'text-brand-700' : 'text-slate-600 hover:text-brand-700' }}">Transformateurs</a>

                <form action="{{ route('products.index') }}" method="GET" class="flex" role="search">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit…" aria-label="Rechercher" class="tv-input w-full lg:w-56">
                </form>

                @auth
                    @if(auth()->user()->canAccessBackOffice())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-brand-700">Back-office</a>
                    @endif
                    <a href="{{ route('consumer.profile') }}" class="text-sm font-medium {{ request()->routeIs('consumer.*') ? 'text-brand-700' : 'text-slate-600 hover:text-brand-700' }}">Mon espace</a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-brand-700">
                        <x-avatar :user="auth()->user()" class="h-8 w-8 text-xs" /> Mon compte
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="tv-btn-secondary">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="tv-btn-secondary">Connexion</a>
                    <a href="{{ route('register') }}" class="tv-btn-primary">Inscription</a>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        <x-flash-messages />
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-brand-100 bg-brand-800 text-brand-50">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div>
                <p class="font-display text-2xl font-semibold">Trace Verte</p>
                <p class="mt-2 text-sm text-brand-100/80">De la ferme à l’assiette : une traçabilité alimentaire claire, vérifiable et responsable.</p>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-200">Explorer</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a class="hover:underline" href="{{ route('products.index') }}">Produits</a></li>
                    <li><a class="hover:underline" href="{{ route('producers.index') }}">Producteurs</a></li>
                    <li><a class="hover:underline" href="{{ route('transformers.index') }}">Transformateurs</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-200">Confidentialité</p>
                <p class="mt-3 text-sm text-brand-100/80">Les scores et analyses sont informatifs. Ils ne constituent ni un diagnostic de santé ni une preuve juridique de fraude.</p>
            </div>
        </div>
        <div class="border-t border-brand-700/60 px-4 py-4 text-center text-xs text-brand-200">© {{ date('Y') }} Trace Verte</div>
    </footer>
    <script>
        (() => {
            const toggle = document.querySelector('[data-menu-toggle]');
            const menu = document.querySelector('[data-menu]');
            toggle?.addEventListener('click', () => {
                const open = menu.classList.toggle('hidden') === false;
                menu.classList.toggle('flex', open);
                toggle.setAttribute('aria-expanded', open);
            });
        })();
    </script>
</body>
</html>
