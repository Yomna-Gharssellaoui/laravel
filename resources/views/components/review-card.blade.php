@props(['review'])

<article class="rounded-2xl border border-slate-200 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            @if($review->user)
                <x-avatar :user="$review->user" class="h-8 w-8" />
            @endif
            <div>
                <p class="text-sm font-semibold text-slate-900">{{ $review->user?->name ?? 'Consommateur' }}</p>
                <p class="text-xs text-slate-600">{{ $review->created_at?->format('d/m/Y') }}@if($review->verified_purchase) · <span class="font-medium text-emerald-800">achat vérifié</span>@endif</p>
            </div>
        </div>
        <p class="text-sm font-semibold text-amber-800" aria-label="Note : {{ $review->rating }} sur 5">
            <span aria-hidden="true">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
            {{ $review->rating }}/5
        </p>
    </div>
    @if($review->title)
        <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $review->title }}</h3>
    @endif
    @if($review->comment)
        <p class="mt-1 text-sm text-slate-700">{{ $review->comment }}</p>
    @endif
</article>
