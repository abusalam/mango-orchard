<x-site-layout :title="'Cookie settings — Aamar Malda'">
    <section
        class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12"
        x-data="{
            analytics: @js($analyticsOptedIn),
            saved: false,
            current: @js($current),
            save() {
                const value = this.analytics ? 'all' : 'necessary';
                const oneYear = 60 * 60 * 24 * 365;
                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = `cookie_consent=${value}; path=/; max-age=${oneYear}; SameSite=Lax${secure}`;
                this.saved = true;
                this.current = value;
                // Reload so a brand-new session is issued for forms / so analytics
                // gating reflects the new choice on the next request.
                setTimeout(() => window.location.reload(), 300);
            }
        }"
    >
        <header class="mb-8" data-testid="cookies-policy-header">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900">Cookie settings</h1>
            <p class="mt-3 text-stone-600 max-w-3xl leading-relaxed">
                Welcome to the Cookie Settings page, where you have the power to tailor your browsing experience. Here, you&rsquo;ll find detailed information about the cookies we use, categorised as <strong>Essential</strong> and <strong>Optional</strong>. Make informed choices that align with your privacy preferences.
            </p>

            @if ($current)
                <p class="mt-4 inline-flex items-center px-3 py-1 rounded-full bg-stone-100 text-stone-700 text-xs">
                    Your current choice:
                    <strong class="ml-1">
                        @if ($current === 'all') Essential + Optional cookies
                        @elseif ($current === 'necessary') Essential cookies only
                        @else {{ $current }}
                        @endif
                    </strong>
                </p>
            @else
                <p class="mt-4 inline-flex items-center px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs" data-testid="cookies-no-choice-yet">
                    You haven&rsquo;t made a choice yet &mdash; pick below and click <strong class="mx-1">Save preferences</strong>.
                </p>
            @endif
        </header>

        {{-- Essential cookies ---------------------------------------- --}}
        <section class="mb-10" data-testid="cookies-essential-section">
            <h2 class="text-sm font-bold uppercase tracking-wider text-stone-500 mb-3">Essential cookies</h2>
            <div class="bg-white rounded-2xl border border-stone-200 divide-y divide-stone-100 overflow-hidden">
                @foreach ($essential as $cookie)
                    <article class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-stone-900 break-words">{{ $cookie['name'] }}</h3>
                            <p class="mt-1 text-sm text-stone-600 leading-relaxed">{{ $cookie['purpose'] }}</p>
                            <p class="mt-2 text-xs text-stone-500">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 text-stone-600 font-medium">Essential for site functionality</span>
                                <span class="ml-2">Lifetime: {{ $cookie['lifetime'] }}</span>
                            </p>
                        </div>
                        <div class="shrink-0">
                            {{-- Locked-on indicator, mirrors india.gov.in's Off / On style --}}
                            <div class="inline-flex items-center gap-2 text-xs font-medium select-none" aria-label="Essential cookie, always on">
                                <span class="w-6 text-right text-stone-400">Off</span>
                                <span class="inline-flex w-11 h-6 items-center rounded-full bg-emerald-500 cursor-not-allowed" title="Required — cannot be disabled">
                                    <span class="inline-block w-5 h-5 rounded-full bg-white shadow translate-x-5"></span>
                                </span>
                                <span class="w-6 text-left text-emerald-700 font-semibold">On</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Optional cookies ---------------------------------------- --}}
        <section class="mb-10" data-testid="cookies-optional-section">
            <h2 class="text-sm font-bold uppercase tracking-wider text-stone-500 mb-3">Optional cookies</h2>
            <div class="bg-white rounded-2xl border border-stone-200 divide-y divide-stone-100 overflow-hidden">
                @foreach ($optional as $cookie)
                    <article class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-stone-900">{{ $cookie['name'] }}</h3>
                            <p class="mt-1 text-sm text-stone-600 leading-relaxed">{{ $cookie['purpose'] }}</p>
                            <p class="mt-2 text-xs text-stone-500">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 font-medium border border-amber-100">Activated based on your preference</span>
                                <span class="ml-2">Lifetime: {{ $cookie['lifetime'] }}</span>
                            </p>
                        </div>
                        <div class="shrink-0">
                            <div class="inline-flex items-center gap-2 text-xs font-medium select-none" data-testid="cookies-analytics-toggle">
                                <button
                                    type="button"
                                    x-on:click="analytics = false; saved = false"
                                    class="w-6 text-right transition-colors"
                                    :class="!analytics ? 'text-stone-900 font-semibold' : 'text-stone-400 hover:text-stone-600'"
                                    aria-label="Set activity analytics off"
                                >Off</button>
                                <button
                                    type="button"
                                    role="switch"
                                    x-on:click="analytics = !analytics; saved = false"
                                    x-bind:aria-checked="analytics ? 'true' : 'false'"
                                    class="inline-flex w-11 h-6 items-center rounded-full transition-colors duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                    :class="analytics ? 'bg-emerald-500' : 'bg-stone-300'"
                                    aria-label="Toggle activity analytics"
                                >
                                    <span
                                        class="inline-block w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 ease-out"
                                        :class="analytics ? 'translate-x-5' : 'translate-x-0.5'"
                                    ></span>
                                </button>
                                <button
                                    type="button"
                                    x-on:click="analytics = true; saved = false"
                                    class="w-6 text-left transition-colors"
                                    :class="analytics ? 'text-emerald-700 font-semibold' : 'text-stone-400 hover:text-stone-600'"
                                    aria-label="Set activity analytics on"
                                >On</button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Save button ---------------------------------------- --}}
        <div class="flex flex-wrap items-center gap-4">
            <button
                type="button"
                x-on:click="save()"
                class="inline-flex items-center px-6 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm shadow-sm"
                data-testid="cookies-save-preferences"
            >Save preferences</button>
            <span x-show="saved" x-cloak class="text-sm text-emerald-700" data-testid="cookies-save-feedback">
                Saved. Reloading&hellip;
            </span>
            <a href="{{ route('home') }}" class="text-sm text-stone-600 hover:text-stone-900 underline">Back to home</a>
        </div>

        {{-- FAQ — mirrors india.gov.in's 4 collapsible questions ---------------------------------------- --}}
        <section class="mt-12" data-testid="cookies-faq">
            <h2 class="text-xl font-semibold text-stone-900 mb-4">Frequently asked questions</h2>
            <div class="space-y-2">
                @php
                    $faqs = [
                        [
                            'q' => 'What are cookies?',
                            'a' => 'Cookies are small text files placed in your browser by the websites you visit. They are widely used to make websites work more efficiently, to remember choices you make (such as your sign-in state), and to provide reporting information to the site owners.',
                        ],
                        [
                            'q' => 'What happens if I disable optional cookies?',
                            'a' => 'Essential cookies will continue to work, so you can still browse, sign in, and submit forms. Disabling Optional cookies means your activity (creating listings, updating settings) will not be recorded in the activity feed beyond the sign-in and administrative account-access events kept as a security audit.',
                        ],
                        [
                            'q' => 'How do I change my choice later?',
                            'a' => 'Return to this page at any time and click Save preferences again with your new selection. You can also access this page from the "Cookie preferences" link in the site footer.',
                        ],
                        [
                            'q' => 'Do you share my data with third parties?',
                            'a' => 'No. Everything recorded on this site stays on this site. We do not load third-party trackers, ad pixels, or analytics SDKs.',
                        ],
                    ];
                @endphp
                @foreach ($faqs as $i => $faq)
                    <details class="bg-white rounded-xl border border-stone-200 p-4 group" data-testid="cookies-faq-item-{{ $i }}">
                        <summary class="cursor-pointer font-medium text-stone-900 flex items-center justify-between gap-3">
                            <span>{{ $faq['q'] }}</span>
                            <svg class="w-4 h-4 text-stone-500 transition-transform group-open:rotate-180" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5l3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-stone-600 leading-relaxed">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </section>
</x-site-layout>
