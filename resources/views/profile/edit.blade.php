@extends('layouts.frontend')

@section('title', 'Mon compte')

@section('content')
    <x-page-header eyebrow="Mon compte" title="Paramètres du compte" subtitle="Nom, adresse email, mot de passe et suppression du compte." />

    <section class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div class="tv-card">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="tv-card">
            @include('profile.partials.update-password-form')
        </div>
        <div class="tv-card">
            @include('profile.partials.delete-user-form')
        </div>
    </section>
@endsection
