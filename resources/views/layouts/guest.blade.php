<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Trace Verte') }} - Connexion</title>

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
            --error: #dc2626;
            --success: #16a34a;
            --moss-text: #4f6b5a;

            --font-display: 'Cormorant', serif;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

            --shadow-soft: 0 4px 24px rgba(26, 61, 46, 0.08);
            --shadow-elevated: 0 8px 40px rgba(26, 61, 46, 0.12);
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Light theme only, like the rest of the site (a partial dark palette made text unreadable) */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, var(--mist) 0%, var(--cream) 100%);
            color: var(--forest-midnight);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-container {
            width: 100%;
            max-width: 1100px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-elevated);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            min-height: 600px;
        }

        .auth-visual {
            background: linear-gradient(135deg, var(--forest-midnight), var(--sage-deep));
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .auth-visual::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .visual-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .logo-large {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .visual-title {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .visual-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto;
        }

        .auth-form-wrapper {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header {
            margin-bottom: 2rem;
        }

        .auth-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: var(--sage-deep);
            font-size: 0.9375rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--forest-midnight);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--sage-light);
            border-radius: 12px;
            font-size: 0.9375rem;
            font-family: var(--font-body);
            color: var(--forest-midnight);
            background: var(--cream);
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--sage-deep);
            background: white;
            box-shadow: 0 0 0 4px rgba(74, 99, 87, 0.1);
        }

        .form-input::placeholder {
            color: var(--moss-text);
        }

        .form-error {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border: 2px solid var(--sage-light);
            border-radius: 4px;
            cursor: pointer;
        }

        .form-checkbox label {
            font-size: 0.9375rem;
            color: var(--sage-deep);
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--forest-midnight), var(--sage-deep));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 16px rgba(74, 99, 87, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(74, 99, 87, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            gap: 1rem;
        }

        .form-link {
            font-size: 0.9375rem;
            color: var(--sage-deep);
            text-decoration: none;
            transition: var(--transition);
        }

        .form-link:hover {
            color: var(--forest-midnight);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--sage-deep);
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--sage-light);
        }

        .divider span {
            padding: 0 1rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--sage-light);
        }

        .auth-footer-text {
            color: var(--sage-deep);
            font-size: 0.9375rem;
        }

        .auth-footer-link {
            color: var(--forest-midnight);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .auth-footer-link:hover {
            color: var(--sage-deep);
        }

        .status-message {
            padding: 0.875rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-message.success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
            border: 1px solid rgba(22, 163, 74, 0.2);
        }

        .status-message.error {
            background: rgba(220, 38, 38, 0.1);
            color: var(--error);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .back-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--forest-midnight);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            background: white;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            box-shadow: var(--shadow-soft);
        }

        .back-home:hover {
            transform: translateX(-4px);
            box-shadow: var(--shadow-elevated);
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }
            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .auth-visual {
                display: none;
            }

            .auth-form-wrapper {
                padding: 2rem 1.5rem;
            }

            .back-home {
                top: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="{{ route('home') }}" class="back-home">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 10H5M5 10L10 15M5 10L10 5"/>
        </svg>
        <span>Retour à l'accueil</span>
    </a>

    <div class="auth-container">
        <div class="auth-visual">
            <div class="visual-content">
                <div class="logo-large">🌿</div>
                <h1 class="visual-title">Trace Verte</h1>
                <p class="visual-subtitle">
                    La transparence environnementale pour chaque produit. Rejoignez la communauté des consommateurs responsables.
                </p>
            </div>
        </div>

        <div class="auth-form-wrapper">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
