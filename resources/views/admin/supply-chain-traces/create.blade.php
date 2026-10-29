@extends('layouts.backend')

@section('title', 'Ajouter une trace')
@section('page-title', 'Ajouter une trace logistique')

@section('content')
    <div class="tv-card max-w-5xl">
        <form method="POST" action="{{ route('admin.supply-chain-traces.store') }}" class="space-y-6">
            @csrf
            @include('admin.supply-chain-traces._form')
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.supply-chain-traces.index') }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
