@extends('layouts.backend')

@section('title', 'Modifier certification')
@section('page-title', 'Modifier la certification')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.certificates.update', $item) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.certificates._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.certificates.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
