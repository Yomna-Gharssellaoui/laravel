@extends('layouts.backend')

@section('title', 'Ajouter une déclaration')
@section('page-title', 'Ajouter une déclaration')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.environmental-claims.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('admin.environmental-claims._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.environmental-claims.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
