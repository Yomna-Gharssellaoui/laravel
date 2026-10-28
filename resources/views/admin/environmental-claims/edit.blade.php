@extends('layouts.backend')

@section('title', 'Modifier la déclaration')
@section('page-title', 'Modifier la déclaration')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.environmental-claims.update', $item) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.environmental-claims._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.environmental-claims.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
