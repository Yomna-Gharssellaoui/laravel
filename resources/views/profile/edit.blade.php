@extends('layouts.frontend')

@section('title', 'Mon compte')

@section('content')
    <x-page-header eyebrow="Mon compte" title="Paramètres du compte" subtitle="Photo, nom, adresse email, mot de passe, double authentification et suppression du compte." />

    <section class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div class="tv-card">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="tv-card">
            @include('profile.partials.update-password-form')
        </div>
        <div class="tv-card">
            @include('profile.partials.two-factor-form')
        </div>
        @if(auth()->user()->role === 'consumer')
            <div class="tv-card flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Compte professionnel</h2>
                    <p class="mt-1 text-sm text-slate-600">Vous êtes producteur, transformateur ou distributeur ? Demandez l’accès au back-office.</p>
                </div>
                <a href="{{ route('pro-account.create') }}" class="tv-btn-primary shrink-0">Demander un compte professionnel</a>
            </div>
        @endif
        <div class="tv-card">
            @include('profile.partials.delete-user-form')
        </div>
    </section>
@endsection
