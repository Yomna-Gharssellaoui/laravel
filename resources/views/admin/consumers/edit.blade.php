@extends('layouts.backend')

@section('title', 'Modifier le profil consommateur')
@section('page-title', 'Modifier le profil consommateur')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.consumers.update', $item) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.consumers._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.consumers.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
