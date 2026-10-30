@props(['status' => 'pending', 'type' => '', 'label' => ''])

@php
    $classes = match ($status) {
        'verified' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
        'expired', 'rejected' => 'bg-red-50 text-red-800 border-red-200',
        default => 'bg-slate-50 text-slate-700 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium {$classes}"]) }}>
    <span>{{ $label ?: $type }}</span>
    <x-status-badge :status="$status" />
</span>
