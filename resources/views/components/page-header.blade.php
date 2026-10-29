@props(['eyebrow' => null, 'title', 'subtitle' => null])

<section class="border-b border-brand-100 bg-gradient-to-b from-brand-50 to-[var(--tv-surface)]">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {{ $slot }}
        @if($eyebrow)
            <span class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-800">{{ $eyebrow }}</span>
        @endif
        <h1 class="mt-4 font-display text-4xl font-semibold text-brand-900 sm:text-5xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-3 max-w-2xl text-lg text-slate-600">{{ $subtitle }}</p>
        @endif
    </div>
</section>
