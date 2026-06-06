@props(['advisory', 'compact' => false])

@php
    $severityStyles = match ($advisory->severity) {
        \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_URGENT => 'bg-rose-50 border-rose-300',
        \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_WARNING => 'bg-amber-50 border-amber-300',
        default => 'bg-white border-stone-200',
    };
    $severityChip = match ($advisory->severity) {
        \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_URGENT => 'bg-rose-200 text-rose-900 border-rose-300',
        \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_WARNING => 'bg-amber-200 text-amber-900 border-amber-300',
        default => 'bg-stone-100 text-stone-700 border-stone-200',
    };
    $categoryLabel = \App\Modules\MangoOrchard\Models\Advisory::CATEGORIES[$advisory->category] ?? Str::headline($advisory->category);
    $severityLabel = \App\Modules\MangoOrchard\Models\Advisory::SEVERITIES[$advisory->severity] ?? Str::headline($advisory->severity);
@endphp

<article
    class="rounded-2xl border {{ $severityStyles }} {{ $compact ? 'p-4' : 'p-5 sm:p-6' }} hover:shadow-md transition-shadow"
    data-testid="advisory-card"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $severityChip }}" data-testid="advisory-severity">{{ $severityLabel }}</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-700 border border-stone-200">{{ $categoryLabel }}</span>
            @if ($advisory->isGeneral())
                <span class="text-[11px] text-stone-500">All varieties</span>
            @else
                <span class="text-[11px] text-stone-500">{{ $advisory->varieties->pluck('name')->join(' · ') }}</span>
            @endif
        </div>
        @if ($advisory->issued_at)
            <p class="text-[11px] text-stone-400" title="{{ $advisory->issued_at }}">Issued {{ $advisory->issued_at->diffForHumans() }}</p>
        @endif
    </div>

    <div class="mt-3 {{ $advisory->image_url ? 'sm:flex sm:gap-4' : '' }}">
        @if ($advisory->image_url)
            <a href="{{ route('advisories.show', $advisory) }}" class="block shrink-0 sm:order-1">
                <img src="{{ $advisory->image_url }}" alt="{{ $advisory->title }}" loading="lazy"
                     class="w-full sm:w-32 h-32 sm:h-24 object-cover rounded-lg border border-stone-200" data-testid="advisory-card-image">
            </a>
        @endif
        <div class="mt-3 sm:mt-0 min-w-0 flex-1">
            <h3 class="text-lg font-semibold tracking-tight text-stone-900">
                <a href="{{ route('advisories.show', $advisory) }}" class="hover:text-orange-700">{{ $advisory->title }}</a>
            </h3>

            @unless ($compact)
                <p class="mt-2 text-sm text-stone-700 line-clamp-3 whitespace-pre-line">{{ Str::limit($advisory->body, 280) }}</p>
            @endunless
        </div>
    </div>

    @unless ($compact)

        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-stone-500">
            @if ($advisory->issuer)
                <span>by <strong class="text-stone-700">{{ $advisory->issuer->name }}</strong></span>
            @endif
            @if ($advisory->expires_at)
                <span>· valid until {{ $advisory->expires_at->toFormattedDateString() }}</span>
            @endif
        </div>
    @endunless
</article>
