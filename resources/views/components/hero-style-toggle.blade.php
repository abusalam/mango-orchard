@props([
    'current',         // identifier of the currently rendered hero variant (informational; used by Alpine to confirm state)
    'next',            // identifier the toggle should switch to
    'label',           // visible label, e.g. "Orchard view"
    'tone' => 'light', // 'light' for buttons over dark hero backdrops; 'dark' for buttons over light/photo backdrops
])

@php
    $toneClasses = $tone === 'light'
        ? 'bg-white/15 hover:bg-white/25 text-white border-white/30 backdrop-blur'
        : 'bg-white/80 hover:bg-white dark:bg-stone-900/80 dark:hover:bg-stone-900 text-stone-800 dark:text-stone-100 border-stone-200 dark:border-stone-700 backdrop-blur';
@endphp

<button type="button"
        data-hero-style-current="{{ $current }}"
        data-hero-style-next="{{ $next }}"
        onclick="document.cookie = 'hero_style={{ $next }}; path=/; max-age=31536000; SameSite=Lax'; location.reload();"
        class="absolute top-4 right-4 z-20 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition-colors {{ $toneClasses }}"
        aria-label="Switch hero view to {{ $label }}"
        data-testid="hero-style-toggle">
    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M3 10h14M13 6l4 4-4 4M7 14l-4-4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span>{{ $label }}</span>
</button>
