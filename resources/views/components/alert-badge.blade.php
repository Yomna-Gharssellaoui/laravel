@props(['alert'])

@php
    $styles = [
        'critical' => 'bg-red-100 text-red-900',
        'high' => 'bg-orange-100 text-orange-900',
        'medium' => 'bg-amber-100 text-amber-900',
        'low' => 'bg-slate-100 text-slate-800',
    ];
    $labels = ['critical' => 'Critique', 'high' => 'Élevée', 'medium' => 'Moyenne', 'low' => 'Faible'];
@endphp

<span {{ $attributes->merge(['class' => 'tv-badge '.($styles[$alert->severity] ?? $styles['low'])]) }}>
    {{ $labels[$alert->severity] ?? ucfirst((string) $alert->severity) }} · {{ str_replace('_', ' ', $alert->alert_type ?? 'alerte') }}
</span>
