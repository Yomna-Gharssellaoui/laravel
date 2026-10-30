@props(['title' => 'Aucune donnée', 'message' => 'Il n’y a rien à afficher pour le moment.'])

<div class="px-4 py-10 text-center">
    <p class="font-display text-lg font-semibold text-brand-800">{{ $title }}</p>
    <p class="mt-1 text-sm text-slate-600">{{ $message }}</p>
    {{ $slot }}
</div>
