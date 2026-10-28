@extends('layouts.backend')

@section('title', 'Ajouter produit')
@section('page-title', 'Ajouter un produit')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('admin.products._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.products.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
