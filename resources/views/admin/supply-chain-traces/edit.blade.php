@extends('layouts.backend')

@section('title', 'Modifier la trace')
@section('page-title', 'Modifier la trace logistique')

@section('content')
    <div class="tv-card max-w-5xl">
        <form method="POST" action="{{ route('admin.supply-chain-traces.update', $item) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.supply-chain-traces._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.supply-chain-traces.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
