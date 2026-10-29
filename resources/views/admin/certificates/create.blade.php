@extends('layouts.backend')

@section('title', 'Ajouter certification')
@section('page-title', 'Ajouter une certification')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.certificates.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('admin.certificates._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.certificates.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
