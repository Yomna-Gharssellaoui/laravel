@extends('layouts.backend')

@section('title', 'Modifier produit')
@section('page-title', 'Modifier le produit')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.products.update', $item) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.products._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.products.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
