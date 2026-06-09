{{-- Floating "back to top" button with a circular progress ring that
     tracks how far down the page the visitor has scrolled. Hidden until
     they pass ~300px (avoids cluttering above-the-fold), then fades in.
     Click → smooth scroll to top. Alpine factory below; using a global
     window function so multiple include sites can share one definition. --}}
<button
    x-data="scrollToTop()"
    x-init="init()"
    x-show="visible"
    x-cloak
    x-transition.opacity.duration.200ms
    @click="scrollUp()"
    type="button"
    aria-label="Scroll to top"
    class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-stone-900 dark:bg-stone-800 text-stone-100 shadow-lg z-40 hover:bg-stone-800 dark:hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-colors flex items-center justify-center"
    data-testid="scroll-to-top"
>
    {{-- SVG progress ring — viewBox 0..36 + r=15.9155 chosen so the
         circle's circumference is exactly 100, so stroke-dashoffset
         maps 1-to-1 with a 0..100 progress value (no math gymnastics
         to translate between percentage and arc length). --}}
    <svg class="absolute inset-0 w-full h-full -rotate-90 overflow-visible" viewBox="0 0 36 36" fill="none" aria-hidden="true">
        {{-- Background track --}}
        <circle cx="18" cy="18" r="15.9155" stroke="currentColor" stroke-width="2" class="opacity-30"/>
        {{-- Filled arc — stroke-linecap rounded so the leading edge
             looks like a tick mark, not a hard cap. --}}
        <circle
            cx="18" cy="18" r="15.9155"
            stroke="currentColor" stroke-width="2.5"
            stroke-dasharray="100"
            :stroke-dashoffset="100 - progress"
            stroke-linecap="round"
            class="text-amber-400"
        />
    </svg>
    {{-- Up-chevron icon centred inside the ring. --}}
    <svg class="relative w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="5 12 10 7 15 12"/>
    </svg>
</button>

<script>
    window.scrollToTop = window.scrollToTop || function () {
        return {
            visible: false,
            progress: 0,  // 0..100

            init() {
                this.update();
                window.addEventListener('scroll', () => this.update(), { passive: true });
                window.addEventListener('resize', () => this.update(), { passive: true });
            },

            update() {
                var scrolled = window.scrollY;
                var max = Math.max(1,
                    document.documentElement.scrollHeight - window.innerHeight,
                );
                this.progress = Math.min(100, Math.max(0, (scrolled / max) * 100));
                // 300px threshold — far enough that the button doesn't
                // appear on short pages or above the fold, near enough
                // that one extra screen of scroll triggers it.
                this.visible = scrolled > 300;
            },

            scrollUp() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        };
    };
</script>
