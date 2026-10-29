@props(['certificate'])
@php($days = $certificate->daysUntilExpiry())
@if($days === null)
    <span class="text-xs text-slate-400">—</span>
@elseif($days < 0)
    <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Expirée</span>
@elseif($days <= 30)
    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Expire dans {{ $days }} j</span>
@else
    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $certificate->expiry_date->format('d/m/Y') }}</span>
@endif
