@props(['status'])

@php
    $map = [
        'published' => 'bg-emerald-100 text-emerald-800',
        'draft' => 'bg-slate-100 text-slate-700',
        'archived' => 'bg-slate-200 text-slate-600',
        'pending' => 'bg-amber-100 text-amber-800',
        'verified' => 'bg-emerald-100 text-emerald-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-orange-100 text-orange-800',
        'open' => 'bg-red-100 text-red-800',
        'investigating' => 'bg-amber-100 text-amber-900',
        'resolved' => 'bg-emerald-100 text-emerald-800',
        'dismissed' => 'bg-slate-100 text-slate-600',
        'in_transit' => 'bg-sky-100 text-sky-800',
        'delivered' => 'bg-emerald-100 text-emerald-800',
        'delayed' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-brand-100 text-brand-800',
        'low' => 'bg-slate-100 text-slate-700',
        'medium' => 'bg-amber-100 text-amber-800',
        'high' => 'bg-orange-100 text-orange-800',
        'critical' => 'bg-red-100 text-red-800',
        'admin' => 'bg-brand-100 text-brand-800',
        'producer' => 'bg-lime-100 text-lime-800',
        'transformer' => 'bg-teal-100 text-teal-800',
        'distributor' => 'bg-cyan-100 text-cyan-800',
        'consumer' => 'bg-indigo-100 text-indigo-800',
        '1' => 'bg-amber-100 text-amber-800',
        '0' => 'bg-slate-100 text-slate-600',
    ];
    $value = is_bool($status) ? ($status ? '1' : '0') : (string) ($status ?? '—');
    $class = $map[$value] ?? 'bg-slate-100 text-slate-700';
    $labels = [
        'published' => 'publié', 'draft' => 'brouillon', 'archived' => 'archivé',
        'pending' => 'en attente', 'verified' => 'vérifié', 'approved' => 'approuvé', 'rejected' => 'rejeté',
        'expired' => 'expiré', 'open' => 'ouverte', 'investigating' => 'en cours', 'resolved' => 'résolue',
        'dismissed' => 'classée', 'in_transit' => 'en transit', 'delivered' => 'livré', 'delayed' => 'en retard',
        'completed' => 'terminé', 'passed' => 'conforme', 'failed' => 'non conforme', 'needs_review' => 'à revoir',
        'low' => 'faible', 'medium' => 'moyenne', 'high' => 'élevée', 'critical' => 'critique',
        'admin' => 'admin', 'producer' => 'producteur', 'transformer' => 'transformateur',
        'distributor' => 'distributeur', 'consumer' => 'consommateur',
    ];
    $label = is_bool($status) ? ($status ? 'oui' : 'non') : ($labels[$value] ?? $status ?? '—');
@endphp

<span {{ $attributes->merge(['class' => "tv-badge {$class}"]) }}>{{ $label }}</span>
