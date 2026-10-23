<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="claude:viewport-fit-sides" content="cover">
    <title>Trace Verte - Transparence Environnementale</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Design System - Luxurious Eco-Conscious */
        :root {
            /* Layout concept: Nature-inspired luxury with organic curves and premium materials */

            /* Color Palette - Sophisticated Earth Tones */
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

            /* Typography */
            --font-display: 'Cormorant', serif;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

            /* Spacing & Rhythm */
            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2.5rem;
            --space-xl: 4rem;
            --space-2xl: 6rem;

            /* Effects */
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--forest-midnight);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            padding-left: max(16px, env(safe-area-inset-left, 0px));
            padding-right: max(16px, env(safe-area-inset-right, 0px));
        }

        /* Navigation */
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
            transition: var(--transition);
            backdrop-filter: blur(20px);
            background: linear-gradient(to bottom, var(--cream) 0%, rgba(250, 249, 246, 0.95) 100%);
        }

        .nav.scrolled {
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
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gold-accent);
            transition: var(--transition);
        }

        .nav-link:hover {
            color: var(--forest-midnight);
        }

        .nav-link:hover::after {
            width: 100%;
        }

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

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-block: var(--space-2xl);
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-2xl);
            align-items: center;
            padding: 0 var(--space-lg);
        }

        .hero-text h1 {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 600;
            line-height: 1.1;
            margin-bottom: var(--space-md);
            color: var(--forest-midnight);
            letter-spacing: -0.03em;
        }

        .hero-text .subtitle {
            font-size: clamp(1.125rem, 2vw, 1.5rem);
            color: var(--sage-deep);
            margin-bottom: var(--space-xl);
            font-weight: 300;
            line-height: 1.6;
        }

        .hero-cta {
            display: flex;
            gap: var(--space-md);
            flex-wrap: wrap;
        }

        .btn-outline {
            background: transparent;
            color: var(--forest-midnight);
            padding: 0.75rem 1.75rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9375rem;
            border: 2px solid var(--sage-light);
            transition: var(--transition);
        }

        .btn-outline:hover {
            border-color: var(--sage-deep);
            background: var(--mist);
        }

        .hero-visual {
            position: relative;
            height: 600px;
        }

        .supply-chain-viz {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .chain-node {
            position: absolute;
            width: 140px;
            height: 140px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-elevated);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: var(--space-xs);
            transition: var(--transition);
            cursor: pointer;
            border: 2px solid var(--mist);
        }

        .chain-node:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 56px rgba(26, 61, 46, 0.16);
            border-color: var(--gold-accent);
        }

        .chain-node.producer {
            top: 20%;
            left: 10%;
            background: linear-gradient(135deg, #f0f9f4, white);
        }

        .chain-node.transformer {
            top: 50%;
            left: 35%;
            background: linear-gradient(135deg, #fef7ed, white);
        }

        .chain-node.distributor {
            top: 25%;
            right: 15%;
            background: linear-gradient(135deg, #f5f0ff, white);
        }

        .chain-node.consumer {
            bottom: 15%;
            right: 5%;
            background: linear-gradient(135deg, #e8f1ed, white);
        }

        .node-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--sage-deep);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .node-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--forest-midnight);
            text-align: center;
        }

        .node-status {
            font-size: 0.75rem;
            color: var(--moss-medium);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
        }

        /* Connection Lines */
        .chain-connections {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .connection-line {
            stroke: var(--sage-light);
            stroke-width: 2;
            stroke-dasharray: 8 4;
            fill: none;
            opacity: 0.5;
        }

        /* Features Section */
        .features {
            padding: var(--space-2xl) var(--space-lg);
            background: var(--mist);
        }

        .features-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: var(--space-2xl);
        }

        .section-tag {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gold-accent);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: var(--space-sm);
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: var(--space-md);
            letter-spacing: -0.02em;
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--sage-deep);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--space-lg);
        }

        .feature-card {
            background: white;
            padding: var(--space-xl);
            border-radius: var(--radius);
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-elevated);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--sage-deep), var(--moss-medium));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: var(--space-md);
        }

        .feature-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: var(--space-sm);
        }

        .feature-description {
            color: var(--sage-deep);
            line-height: 1.7;
        }

        /* Stats Section */
        .stats {
            padding: var(--space-2xl) var(--space-lg);
            background: linear-gradient(135deg, var(--forest-midnight), var(--sage-deep));
            color: white;
        }

        .stats-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-xl);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-family: var(--font-display);
            font-size: clamp(3rem, 5vw, 4rem);
            font-weight: 600;
            margin-bottom: var(--space-xs);
            background: linear-gradient(135deg, white, var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1.125rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* CTA Section */
        .cta {
            padding: var(--space-2xl) var(--space-lg);
            background: var(--cream);
        }

        .cta-content {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
            background: linear-gradient(135deg, var(--mist), white);
            padding: var(--space-2xl);
            border-radius: var(--radius);
            box-shadow: var(--shadow-elevated);
        }

        .cta-title {
            font-family: var(--font-display);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: var(--space-md);
            letter-spacing: -0.02em;
        }

        .cta-description {
            font-size: 1.125rem;
            color: var(--sage-deep);
            margin-bottom: var(--space-xl);
            line-height: 1.7;
        }

        /* Footer */
        .footer {
            padding: var(--space-2xl) var(--space-lg);
            background: var(--forest-midnight);
            color: var(--sage-light);
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-xl);
        }

        .footer-section h4 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            color: white;
            margin-bottom: var(--space-md);
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
        }

        .footer-link {
            color: var(--sage-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: var(--gold-light);
        }

        .footer-bottom {
            max-width: 1400px;
            margin: var(--space-xl) auto 0;
            padding-top: var(--space-lg);
            border-top: 1px solid rgba(184, 206, 192, 0.2);
            text-align: center;
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-content {
                grid-template-columns: 1fr;
                gap: var(--space-xl);
            }

            .hero-visual {
                height: 400px;
            }

            .chain-node {
                width: 100px;
                height: 100px;
            }

            .node-icon {
                width: 36px;
                height: 36px;
                font-size: 1.25rem;
            }

            .hero-cta {
                flex-direction: column;
            }

            .btn-primary,
            .btn-outline {
                text-align: center;
                display: block;
            }
        }

        /* Animations */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .chain-node {
            animation: float 6s ease-in-out infinite;
        }

        .chain-node.producer {
            animation-delay: 0s;
        }

        .chain-node.transformer {
            animation-delay: 1s;
        }

        .chain-node.distributor {
            animation-delay: 2s;
        }

        .chain-node.consumer {
            animation-delay: 3s;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav" id="nav">
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
            @else
                <a href="{{ route('login') }}" class="nav-link">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary">Commencer</a>
            @endauth
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>La transparence environnementale pour chaque produit</h1>
                <p class="subtitle">Suivez l'empreinte écologique de vos produits depuis la source jusqu'à votre table. Une traçabilité complète, une confiance totale.</p>
                <div class="hero-cta">
                    <a href="{{ route('products.index') }}" class="btn-primary">Explorer les produits</a>
                    <a href="#features" class="btn-outline">En savoir plus</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="supply-chain-viz">
                    <svg class="chain-connections" viewBox="0 0 600 600">
                        <path class="connection-line" d="M 120 150 Q 250 250, 300 320" />
                        <path class="connection-line" d="M 300 320 Q 400 300, 480 180" />
                        <path class="connection-line" d="M 480 180 Q 500 350, 520 480" />
                    </svg>

                    <div class="chain-node producer">
                        <div class="node-icon">🌾</div>
                        <div class="node-label">Producteur</div>
                        <div class="node-status">
                            <span class="status-dot"></span>
                            Certifié Bio
                        </div>
                    </div>

                    <div class="chain-node transformer">
                        <div class="node-icon">⚙️</div>
                        <div class="node-label">Transformateur</div>
                        <div class="node-status">
                            <span class="status-dot"></span>
                            Éco-responsable
                        </div>
                    </div>

                    <div class="chain-node distributor">
                        <div class="node-icon">🚛</div>
                        <div class="node-label">Distributeur</div>
                        <div class="node-status">
                            <span class="status-dot"></span>
                            Local
                        </div>
                    </div>

                    <div class="chain-node consumer">
                        <div class="node-icon">🛒</div>
                        <div class="node-label">Consommateur</div>
                        <div class="node-status">
                            <span class="status-dot"></span>
                            Vous
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-content">
            <div class="section-header">
                <div class="section-tag">Nos Solutions</div>
                <h2 class="section-title">Une traçabilité complète et transparente</h2>
                <p class="section-description">
                    Découvrez comment Trace Verte révolutionne la transparence des chaînes d'approvisionnement alimentaires avec des technologies innovantes.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3 class="feature-title">Traçabilité totale</h3>
                    <p class="feature-description">
                        Suivez chaque étape du parcours de vos produits, du producteur au consommateur, avec une transparence absolue et en temps réel.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Empreinte carbone</h3>
                    <p class="feature-description">
                        Analysez l'impact environnemental de chaque produit avec des métriques précises sur les émissions de CO2, la consommation d'eau et d'énergie.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✓</div>
                    <h3 class="feature-title">Certifications vérifiées</h3>
                    <p class="feature-description">
                        Accédez aux certifications authentiques et vérifiées : Bio, Éco-responsable, Commerce équitable, et bien plus encore.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3 class="feature-title">Analyse IA</h3>
                    <p class="feature-description">
                        Notre intelligence artificielle évalue automatiquement la durabilité et propose des alternatives plus écologiques.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3 class="feature-title">Alertes personnalisées</h3>
                    <p class="feature-description">
                        Recevez des notifications en temps réel sur les changements de certification, les rappels de produits et les nouvelles alternatives durables.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3 class="feature-title">Avis communauté</h3>
                    <p class="feature-description">
                        Partagez vos expériences et consultez les avis d'autres consommateurs engagés pour faire les meilleurs choix écologiques.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-content">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Produits tracés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Producteurs certifiés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">95%</div>
                    <div class="stat-label">Satisfaction client</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">30%</div>
                    <div class="stat-label">Réduction CO₂ moyenne</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2 class="cta-title">Prêt à faire des choix plus durables ?</h2>
            <p class="cta-description">
                Rejoignez des milliers de consommateurs conscients qui utilisent Trace Verte pour faire des choix alimentaires responsables et transparents.
            </p>
            <div class="hero-cta" style="justify-content: center;">
                <a href="{{ route('register') }}" class="btn-primary">Créer un compte gratuit</a>
                <a href="{{ route('products.index') }}" class="btn-outline">Voir les produits</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Trace Verte</h4>
                <p style="line-height: 1.7;">
                    La plateforme de transparence environnementale pour une consommation responsable et éclairée.
                </p>
            </div>
            <div class="footer-section">
                <h4>Produits</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('products.index') }}" class="footer-link">Tous les produits</a></li>
                    <li><a href="{{ route('producers.index') }}" class="footer-link">Producteurs</a></li>
                    <li><a href="{{ route('transformers.index') }}" class="footer-link">Transformateurs</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Entreprise</h4>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">À propos</a></li>
                    <li><a href="#" class="footer-link">Notre mission</a></li>
                    <li><a href="#" class="footer-link">Contact</a></li>
                    <li><a href="#" class="footer-link">Carrières</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Légal</h4>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">Confidentialité</a></li>
                    <li><a href="#" class="footer-link">Conditions</a></li>
                    <li><a href="#" class="footer-link">Cookies</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Trace Verte. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        const nav = document.getElementById('nav');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 100) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
