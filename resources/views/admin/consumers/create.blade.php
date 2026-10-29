@extends('layouts.backend')

@section('title', 'Ajouter un profil consommateur')
@section('page-title', 'Ajouter un profil consommateur')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.consumers.store') }}" class="space-y-6">
            @csrf
            
            @include('admin.consumers._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.consumers.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
