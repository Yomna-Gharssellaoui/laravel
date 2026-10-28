@extends('layouts.backend')

@section('title', 'Modifier la vérification')
@section('page-title', 'Modifier la vérification de conformité')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.compliance-checks.update', $item) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.compliance-checks._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.compliance-checks.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
