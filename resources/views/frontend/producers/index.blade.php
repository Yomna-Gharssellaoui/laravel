@extends('layouts.frontend')

@section('title', 'Producteurs')

@section('content')
    <x-page-header eyebrow="Acteurs" title="Producteurs engagés" subtitle="Des fermes locales et responsables, avec une démarche de qualité et de transparence." />

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if($producers->isEmpty())
            <x-empty-state title="Aucun producteur" message="Les profils des producteurs apparaîtront ici dès leur création." />
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($producers as $producer)
                    <x-producer-card :producer="$producer" />
                @endforeach
            </div>
            <div class="mt-10">{{ $producers->links() }}</div>
        @endif
    </section>
@endsection
