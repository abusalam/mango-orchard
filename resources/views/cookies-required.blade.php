<x-site-layout :title="'Cookies required — '.config('app.name')">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-16">
        <div class="rounded-2xl border border-amber-200 dark:border-stone-800 bg-white dark:bg-stone-950 shadow-sm p-8 sm:p-10" data-testid="cookies-required-card">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-700">
                    {{-- biscuit / cookie glyph --}}
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-4-4 4 4 0 0 1-4-4 4 4 0 0 1-2-2z"/>
                        <circle cx="9" cy="10" r="1"/>
                        <circle cx="15" cy="13" r="1"/>
                        <circle cx="10" cy="15" r="1"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">Cookies are needed for this</h1>
                    <p class="mt-2 text-stone-600 dark:text-stone-300 leading-relaxed">
                        The page you tried to open needs cookies to work &mdash; for example, to keep you signed in, protect forms from cross-site forgery, or remember what you typed between steps.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid sm:grid-cols-2 gap-4">
                <div class="rounded-xl border border-stone-200 dark:border-stone-800 p-4 bg-stone-50 dark:bg-stone-900">
                    <h2 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Necessary only</h2>
                    <p class="mt-1 text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Enables sign-in, registration, marketplace listings, and other forms. Only sign-in and administrative account-access events are recorded as a security audit.
                    </p>
                </div>
                <div class="rounded-xl border border-stone-200 dark:border-stone-800 p-4 bg-stone-50 dark:bg-stone-900">
                    <h2 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Accept all</h2>
                    <p class="mt-1 text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Everything above, plus activity events (listings, advisories, settings changes&hellip;) appear in the admin telemetry feed.
                    </p>
                </div>
            </div>

            <p class="mt-6 text-sm text-stone-600 dark:text-stone-300">
                Pick a preference in the banner at the bottom of the page. We'll take you {{ $return ? 'back to where you were going' : 'wherever you'.chr(8217).'d like to go' }} as soon as you choose.
            </p>

            @if ($return)
                {{-- Persist the return URL across the banner-triggered reload by carrying it as a query param the banner sees. --}}
                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400" data-testid="cookies-required-return">
                    Heading to: <code class="text-stone-700 dark:text-stone-300">{{ $return }}</code>
                </p>
            @endif

            <div class="mt-8 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 rounded-full border border-stone-300 text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:bg-stone-900 transition-colors">
                    Back to home
                </a>
                <a href="{{ route('varieties.index') }}" class="inline-flex items-center px-4 py-2 rounded-full border border-stone-300 text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:bg-stone-900 transition-colors">
                    Browse varieties
                </a>
                <a href="{{ route('listings.index') }}" class="inline-flex items-center px-4 py-2 rounded-full border border-stone-300 text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:bg-stone-900 transition-colors">
                    Browse marketplace
                </a>
            </div>
        </div>
    </section>
</x-site-layout>
