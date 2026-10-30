@props(['level' => 'neutral', 'title' => null])
@php
    $classes = [
        'good' => 'bg-emerald-100 text-emerald-800',
        'warn' => 'bg-amber-100 text-amber-800',
        'bad' => 'bg-red-100 text-red-800',
        'neutral' => 'bg-violet-100 text-violet-800',
    ][$level] ?? 'bg-violet-100 text-violet-800';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-0.5 text-xs font-medium $classes"]) }} @if($title) title="{{ $title }}" @endif>{{ $slot }}</span>
