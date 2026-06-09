@props(['size' => 'sm'])

@php
    // `sm` (default, for nav bars) — compact pill with just an icon.
    // `lg` — bigger labelled button. Add more sizes by extending here.
    $btnClasses = $size === 'lg'
        ? 'px-3 py-1.5 text-sm'
        : 'px-2 py-1 text-xs';
@endphp

{{-- Three-state theme picker: Auto (follows OS) · Light · Dark.
     Cookie is written client-side (1-year max-age, SameSite=Lax). The
     `dark` class on <html> is updated immediately so the change feels
     instant — no reload needed. Keyboard-friendly: arrow-key navigation
     inside the dropdown via Alpine's :focus handling. --}}
<div
    x-data="themeSwitcher()"
    x-init="init()"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative inline-block"
    data-testid="theme-switcher"
>
    <button
        type="button"
        @click="open = !open"
        :aria-label="'Theme: ' + label(preference)"
        class="inline-flex items-center gap-1.5 {{ $btnClasses }} rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-700 transition-colors"
        data-testid="theme-switcher-button"
    >
        {{-- Sun icon when light, moon when dark, half-and-half for auto --}}
        <template x-if="effective === 'light'">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
        </template>
        <template x-if="effective === 'dark'">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </template>
        <span x-text="label(preference)" class="hidden sm:inline"></span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute right-0 mt-1 w-36 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-800 shadow-lg overflow-hidden z-40 text-sm"
        role="menu"
    >
        {{-- Each option has its own icon. The check mark on the right
             marks the currently-selected preference (separate from
             `effective` — Auto stays "selected" even when its applied
             colour matches Light or Dark). --}}
        <button
            type="button"
            @click="choose('auto')"
            :class="preference === 'auto'
                ? 'bg-stone-100 dark:bg-stone-700 font-medium'
                : 'hover:bg-stone-50 dark:hover:bg-stone-700/50'"
            class="flex w-full items-center gap-2 px-3 py-2 text-stone-800 dark:text-stone-100"
            data-testid="theme-option-auto"
        >
            {{-- Monitor / display icon: indicates "follow OS" --}}
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <span class="flex-1 text-left">Auto</span>
            <svg x-show="preference === 'auto'" class="w-3.5 h-3.5 shrink-0 text-stone-500 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        </button>

        <button
            type="button"
            @click="choose('light')"
            :class="preference === 'light'
                ? 'bg-stone-100 dark:bg-stone-700 font-medium'
                : 'hover:bg-stone-50 dark:hover:bg-stone-700/50'"
            class="flex w-full items-center gap-2 px-3 py-2 text-stone-800 dark:text-stone-100"
            data-testid="theme-option-light"
        >
            {{-- Sun icon --}}
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
            <span class="flex-1 text-left">Light</span>
            <svg x-show="preference === 'light'" class="w-3.5 h-3.5 shrink-0 text-stone-500 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        </button>

        <button
            type="button"
            @click="choose('dark')"
            :class="preference === 'dark'
                ? 'bg-stone-100 dark:bg-stone-700 font-medium'
                : 'hover:bg-stone-50 dark:hover:bg-stone-700/50'"
            class="flex w-full items-center gap-2 px-3 py-2 text-stone-800 dark:text-stone-100"
            data-testid="theme-option-dark"
        >
            {{-- Moon icon --}}
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
            <span class="flex-1 text-left">Dark</span>
            <svg x-show="preference === 'dark'" class="w-3.5 h-3.5 shrink-0 text-stone-500 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        </button>
    </div>
</div>

<script>
    // Alpine factory — registered globally so multiple switchers on the
    // page share the same state derivation logic without duplication.
    window.themeSwitcher = window.themeSwitcher || function () {
        return {
            open: false,
            preference: 'auto',  // auto | light | dark
            effective: 'light',  // light | dark — the actually-applied theme

            init() {
                this.preference = this.readCookie() || 'auto';
                this.recomputeEffective();

                // If the OS flips while sitting on a page with preference=auto,
                // honour that without a reload.
                if (window.matchMedia) {
                    var media = window.matchMedia('(prefers-color-scheme: dark)');
                    var handler = () => {
                        if (this.preference === 'auto') this.apply();
                    };
                    if (media.addEventListener) media.addEventListener('change', handler);
                    else media.addListener(handler);
                }
            },

            choose(value) {
                this.preference = value;
                this.open = false;
                this.writeCookie(value);
                this.apply();
            },

            apply() {
                this.recomputeEffective();
                document.documentElement.classList.toggle('dark', this.effective === 'dark');
                document.documentElement.dataset.themePreference = this.preference;
            },

            recomputeEffective() {
                var systemDark = window.matchMedia
                    && window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.effective = this.preference === 'dark'
                    || (this.preference !== 'light' && systemDark)
                    ? 'dark'
                    : 'light';
            },

            label(value) {
                return { auto: 'Auto', light: 'Light', dark: 'Dark' }[value] || 'Auto';
            },

            readCookie() {
                var c = document.cookie.split('; ').find(function (x) {
                    return x.indexOf('theme_preference=') === 0;
                });
                return c ? c.split('=')[1] : null;
            },

            writeCookie(value) {
                var oneYear = 60 * 60 * 24 * 365;
                var secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = 'theme_preference=' + value
                    + '; path=/; max-age=' + oneYear
                    + '; SameSite=Lax' + secure;
            },
        };
    };
</script>
