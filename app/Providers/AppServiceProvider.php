<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Breeze handles login, registration and password reset; Fortify is only used
        // for its two-factor authentication actions, so its own routes are disabled.
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // TOTP two-factor authentication must be confirmed with a first code before it is active
        config(['fortify.features' => [
            Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
        ]]);

        // Attach a CSP header to every response; in local environment also allow the Vite dev server.
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $response = $event->response;

            // Alpine.js (back-office) evaluates its directives with new Function(),
            // hence 'unsafe-eval' in every environment.
            $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval'";
            $styleSrc = "'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com";
            $connectSrc = "'self'";
            $imgSrc = "'self' data: blob:";

            if (app()->isLocal()) {
                $scriptSrc .= ' http://127.0.0.1:5173';
                $connectSrc .= ' http://127.0.0.1:5173 ws://127.0.0.1:5173';
            }

            $csp = implode('; ', [
                "default-src 'self'",
                "script-src {$scriptSrc}",
                "style-src {$styleSrc}",
                "connect-src {$connectSrc}",
                "img-src {$imgSrc}",
                'font-src https://fonts.bunny.net https://fonts.gstatic.com',
                "frame-ancestors 'none'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        });
    }
}
