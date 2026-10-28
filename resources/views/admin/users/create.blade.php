@extends('layouts.backend')

@section('title', 'Ajouter un utilisateur')
@section('page-title', 'Ajouter un utilisateur')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            @include('admin.users._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.users.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
