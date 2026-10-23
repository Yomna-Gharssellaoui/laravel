<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="claude:viewport-fit-sides" content="cover">
    <title>{{ $title ?? 'Produits' }} - Trace Verte</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --forest-midnight: #1a3d2e;
            --sage-deep: #4a6357;
            --moss-medium: #7d9d88;
            --sage-light: #b8cec0;
            --mist: #e8f1ed;
            --cream: #faf9f6;
            --earth-dark: #2d2520;
            --gold-accent: #c4a572;
            --gold-light: #e3d5b8;
            --terracotta: #c87f5e;

            --font-display: 'Cormorant', serif;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2.5rem;
            --space-xl: 4rem;
            --space-2xl: 6rem;

            --shadow-soft: 0 4px 24px rgba(26, 61, 46, 0.08);
            --shadow-elevated: 0 8px 40px rgba(26, 61, 46, 0.12);
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --forest-midnight: #e8f1ed;
                --sage-deep: #b8cec0;
                --moss-medium: #7d9d88;
                --sage-light: #4a6357;
                --mist: #2a3d35;
                --cream: #1a2821;
                --earth-dark: #faf9f6;
                --gold-accent: #e3d5b8;
                --gold-light: #9d866d;
                --terracotta: #e39d82;
                --shadow-soft: 0 4px 24px rgba(0, 0, 0, 0.3);
                --shadow-elevated: 0 8px 40px rgba(0, 0, 0, 0.5);
                color-scheme: dark;
            }
        }

        :root[data-theme="dark"] {
            --forest-midnight: #e8f1ed;
            --sage-deep: #b8cec0;
            --moss-medium: #7d9d88;
            --sage-light: #4a6357;
            --mist: #2a3d35;
            --cream: #1a2821;
            --earth-dark: #faf9f6;
            --gold-accent: #e3d5b8;
            --gold-light: #9d866d;
            --terracotta: #e39d82;
            --shadow-soft: 0 4px 24px rgba(0, 0, 0, 0.3);
            --shadow-elevated: 0 8px 40px rgba(0, 0, 0, 0.5);
            color-scheme: dark;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--forest-midnight);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: max(16px, env(safe-area-inset-right, 0px));
        }

        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: var(--space-md) max(24px, env(safe-area-inset-left, 0px));
            padding-right: max(24px, env(safe-area-inset-right, 0px));
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(20px);
            background: linear-gradient(to bottom, var(--cream) 0%, rgba(250, 249, 246, 0.95) 100%);
            box-shadow: var(--shadow-soft);
        }

        .logo {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--forest-midnight);
            text-decoration: none;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--sage-deep), var(--moss-medium));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: var(--space-lg);
            align-items: center;
        }

        .nav-link {
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--sage-deep);
            text-decoration: none;
            transition: var(--transition);
        }

        .nav-link:hover { color: var(--forest-midnight); }

        .btn-primary {
            background: linear-gradient(135deg, var(--sage-deep), var(--moss-medium));
            color: white;
            padding: 0.75rem 1.75rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(74, 99, 87, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(74, 99, 87, 0.3);
        }

        .hero-section {
            padding-top: calc(80px + var(--space-2xl));
            padding-bottom: var(--space-2xl);
            background: linear-gradient(135deg, var(--mist), var(--cream));
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
        }

        .section-tag {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gold-accent);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: var(--space-sm);
        }

        .page-title {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: var(--space-md);
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 1.125rem;
            color: var(--sage-deep);
            max-width: 700px;
            line-height: 1.7;
        }

        .search-section {
            margin-top: var(--space-xl);
            background: white;
            padding: var(--space-lg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-elevated);
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: var(--space-md);
        }

        .form-input, .form-select {
            padding: 1rem;
            border: 2px solid var(--sage-light);
            border-radius: 12px;
            font-size: 0.9375rem;
            font-family: var(--font-body);
            color: var(--forest-midnight);
            background: var(--cream);
            transition: var(--transition);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--sage-deep);
            background: white;
            box-shadow: 0 0 0 4px rgba(74, 99, 87, 0.1);
        }

        .btn-search {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--sage-deep), var(--moss-medium));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(74, 99, 87, 0.3);
        }

        .products-section {
            padding: var(--space-2xl) 0;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--space-lg);
        }

        .product-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-elevated);
        }

        .product-image {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, var(--mist), var(--sage-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--sage-deep);
            backdrop-filter: blur(10px);
        }

        .product-content {
            padding: var(--space-lg);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            font-size: 0.875rem;
            color: var(--gold-accent);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: var(--space-xs);
        }

        .product-name {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: var(--space-sm);
        }

        .product-origin {
            font-size: 0.9375rem;
            color: var(--sage-deep);
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-meta {
            display: flex;
            gap: var(--space-md);
            margin-top: auto;
            padding-top: var(--space-md);
            border-top: 1px solid var(--sage-light);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--sage-deep);
        }

        .meta-icon {
            width: 20px;
            height: 20px;
            color: var(--moss-medium);
        }

        .empty-state {
            text-align: center;
            padding: var(--space-2xl);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: var(--space-md);
        }

        .empty-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: var(--space-sm);
        }

        .empty-message {
            font-size: 1.125rem;
            color: var(--sage-deep);
            max-width: 500px;
            margin: 0 auto;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--space-sm);
            margin-top: var(--space-xl);
        }

        .page-link {
            padding: 0.75rem 1.25rem;
            border: 2px solid var(--sage-light);
            border-radius: 10px;
            color: var(--forest-midnight);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .page-link:hover, .page-link.active {
            border-color: var(--sage-deep);
            background: var(--mist);
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .search-form {
                grid-template-columns: 1fr;
            }
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <a href="{{ route('home') }}" class="logo">
            <div class="logo-icon">🌿</div>
            <span>Trace Verte</span>
        </a>
        <div class="nav-links">
            <a href="{{ route('products.index') }}" class="nav-link">Produits</a>
            <a href="{{ route('producers.index') }}" class="nav-link">Producteurs</a>
            <a href="{{ route('transformers.index') }}" class="nav-link">Transformateurs</a>
            @auth
                <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-primary">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary">Commencer</a>
            @endauth
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="section-tag">Catalogue</div>
            <h1 class="page-title">Produits traçables</h1>
            <p class="page-subtitle">
                Explorez notre collection de produits éco-responsables avec une transparence totale sur leur origine, leur transformation et leur impact environnemental.
            </p>

            <!-- Search Form -->
            <div class="search-section">
                <form method="GET" action="{{ route('products.index') }}" class="search-form">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-input"
                        placeholder="Rechercher un produit..."
                    >
                    <select name="category" class="form-select">
                        <option value="">Toutes catégories</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-search">Rechercher</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            @if(isset($products) && $products->isNotEmpty())
                <div class="products-grid">
                    @foreach($products as $product)
                        <a href="{{ route('products.show', $product) }}" class="product-card">
                            <div class="product-image">
                                @if($product->category)
                                    <span class="product-badge">{{ $product->category }}</span>
                                @endif
                                🌾
                            </div>
                            <div class="product-content">
                                @if($product->category)
                                    <div class="product-category">{{ $product->category }}</div>
                                @endif
                                <h3 class="product-name">{{ $product->name }}</h3>
                                @if($product->origin)
                                    <div class="product-origin">
                                        <svg class="meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $product->origin }}
                                    </div>
                                @endif

                                <div class="product-meta">
                                    @if($product->producer)
                                        <div class="meta-item">
                                            <svg class="meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Producteur certifié
                                        </div>
                                    @endif
                                    @if($product->environmentalFootprint)
                                        <div class="meta-item">
                                            <svg class="meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Tracé
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if(method_exists($products, 'links'))
                    <div class="pagination">
                        {{ $products->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h2 class="empty-title">Aucun produit disponible</h2>
                    <p class="empty-message">
                        Les produits publiés apparaîtront ici dès qu'ils seront ajoutés à notre catalogue.
                    </p>
                </div>
            @endif
        </div>
    </section>
</body>
</html>
