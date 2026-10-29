@props(['user'])

@if($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="Photo de {{ $user->name }}" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover border border-brand-100']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-800']) }} aria-hidden="true">{{ $user->initials() }}</span>
@endif
