@extends('layouts.backend')

@section('title', 'Ajouter une vérification')
@section('page-title', 'Ajouter une vérification de conformité')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.compliance-checks.store') }}" class="space-y-6">
            @csrf
            @include('admin.compliance-checks._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.compliance-checks.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
