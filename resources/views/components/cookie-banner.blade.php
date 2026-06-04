{{-- Visitor cookie consent banner.

     - Shows on first visit (when no `cookie_consent` cookie is set).
     - "Accept all" sets `cookie_consent=all` → session + XSRF cookies AND
       behavioural telemetry (listings, advisories, etc.) are recorded.
     - "Necessary only" sets `cookie_consent=necessary` → session + XSRF
       cookies are allowed, but only security-critical telemetry
       (auth.* + administrative account access) is recorded.
     - Until a choice is made, the [`HonorCookieConsent`](app/Http/Middleware/HonorCookieConsent.php)
       middleware strips ALL non-essential cookies from responses. After a
       choice, the page is reloaded so any form on it renders against a
       fresh session (CSRF would otherwise mismatch).
     - The chosen value is stored as a 1-year cookie so the banner doesn't
       re-prompt on every visit.
     - A "Reset cookie preferences" control in the footer clears the cookie. --}}

<div
    x-data="{
        shown: (() => {
            return ! document.cookie.split('; ').some(c => c.startsWith('cookie_consent='));
        })(),
        save(value) {
            const oneYear = 60 * 60 * 24 * 365;
            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `cookie_consent=${value}; path=/; max-age=${oneYear}; SameSite=Lax${secure}`;
            this.shown = false;
            // Reload so the next render gets a session + CSRF token. Without
            // this, any form on the current page still references the
            // session-less render the visitor just consented from.
            window.location.reload();
        }
    }"
    x-show="shown"
    x-cloak
    x-transition.opacity
    role="dialog"
    aria-label="Cookie consent"
    data-testid="cookie-banner"
    class="fixed inset-x-0 bottom-0 z-50 px-4 pb-4 sm:px-6 sm:pb-6 pointer-events-none"
>
    <div class="pointer-events-auto mx-auto max-w-3xl rounded-2xl bg-stone-900 text-stone-100 shadow-2xl border border-stone-700 p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-50">Cookies on Mango Orchard</p>
                <p class="mt-1 text-xs text-stone-300 leading-relaxed">
                    We use a session cookie to keep you signed in (strictly necessary) and, with your consent, behavioural cookies to record activity in the admin telemetry feed. Without analytics consent we only record security events (sign-ins, administrative account access).
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                <button
                    type="button"
                    x-on:click="save('necessary')"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-full border border-stone-500 text-stone-100 text-sm font-medium hover:bg-stone-800 transition-colors"
                    data-testid="cookie-banner-necessary"
                >Necessary only</button>
                <button
                    type="button"
                    x-on:click="save('all')"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-amber-500 text-stone-900 text-sm font-medium hover:bg-amber-400 transition-colors"
                    data-testid="cookie-banner-accept"
                >Accept all</button>
            </div>
        </div>
    </div>
</div>
