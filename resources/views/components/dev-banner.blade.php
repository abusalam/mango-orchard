@if (! empty($devBannerEnabled))
    {{-- Under-development notice. Visible only when the
         `dev_banner_enabled` setting is on (toggle: /admin/settings).
         Dismiss is per-browser via a session cookie — flipping the
         site-wide setting off hides it for everyone immediately. --}}
    <div x-data="{ open: document.cookie.indexOf('dev_banner_dismissed=1') === -1 }"
         x-show="open"
         x-cloak
         class="bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-100 border-b border-amber-200 dark:border-amber-900"
         data-testid="dev-banner">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2 flex items-start sm:items-center gap-3 text-sm">
            <svg class="w-4 h-4 shrink-0 mt-0.5 sm:mt-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.515 2.625H3.72c-1.345 0-2.188-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
            <p class="flex-1">
                <strong>Site under development.</strong>
                Content is preliminary and not yet finalised for public access — please don't rely on numbers, names, or other details shown here.
            </p>
            <button type="button"
                    @click="open = false; document.cookie = 'dev_banner_dismissed=1; path=/; max-age=86400; SameSite=Lax'"
                    class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full hover:bg-amber-200 dark:hover:bg-amber-900 transition-colors"
                    aria-label="Dismiss notice"
                    data-testid="dev-banner-dismiss">
                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
@endif
