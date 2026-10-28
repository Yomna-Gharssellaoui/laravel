@extends('layouts.backend')

@section('title', 'Modifier un utilisateur')
@section('page-title', 'Modifier l’utilisateur')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.users.update', $item) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.users._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.users.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
