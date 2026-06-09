<x-site-layout :title="'Profile — Aamar Malda'">

    @php
        // Sidebar badge counts — surface "needs attention" items so the user
        // doesn't have to scroll to discover them. We sum per-role pending
        // counts directly rather than flatten()-ing (flatten() recurses into
        // Arrayables and converts our Eloquent models to attribute arrays,
        // breaking ->where('status', …) by property access).
        $pendingApplicationsCount = $roleApplicationsByRoleId
            ->sum(fn ($apps) => $apps->where('status', $roleApplicationStatuses['pending'])->count());
        $delegationsActiveCount = $delegationsGranted->count() + $delegationsReceived->count();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:max-w-5xl lg:mx-auto mb-6">
                <h2 class="font-semibold text-xl text-stone-900 dark:text-stone-100 leading-tight">{{ __('Profile') }}</h2>
            </div>
            {{-- Cap the sidebar+content pair to ~1024px and centre it.
                 Forms read more comfortably at this width and the page stops
                 feeling lopsided on a 1440-wide viewport. --}}
            <div class="lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8 lg:max-w-5xl lg:mx-auto">

                {{-- ── Sticky sidebar (desktop) / collapsed nav (mobile) ── --}}
                {{-- Alpine tracks `window.location.hash` so the most-recently
                     clicked link picks up an active style. Clicking a link
                     updates the hash natively → hashchange fires → `active`
                     updates. A deep-link to /profile#preferences also lands
                     with the right item already highlighted. --}}
                <aside class="mb-6 lg:mb-0"
                       x-data="{ active: window.location.hash.slice(1) }"
                       x-on:hashchange.window="active = window.location.hash.slice(1)">
                    <nav class="lg:sticky lg:top-20 bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-2 text-sm" data-testid="profile-sidebar-nav">
                        <a href="#profile-information"
                           x-bind:class="active === 'profile-information' ? 'bg-orange-50 text-orange-900' : 'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700'"
                           x-bind:aria-current="active === 'profile-information' ? 'true' : null"
                           data-testid="sidebar-link-profile-information"
                           class="block px-3 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Profile information') }}
                        </a>
                        <a href="#preferences"
                           x-bind:class="active === 'preferences' ? 'bg-orange-50 text-orange-900' : 'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700'"
                           x-bind:aria-current="active === 'preferences' ? 'true' : null"
                           data-testid="sidebar-link-preferences"
                           class="block px-3 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Orchard preferences') }}
                        </a>
                        <a href="#role-applications"
                           x-bind:class="active === 'role-applications' ? 'bg-orange-50 text-orange-900' : 'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700'"
                           x-bind:aria-current="active === 'role-applications' ? 'true' : null"
                           data-testid="sidebar-link-role-applications"
                           class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg font-medium transition-colors">
                            <span>{{ __('Request a role') }}</span>
                            @if ($pendingApplicationsCount > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-900 border border-amber-200 dark:border-stone-800" data-testid="sidebar-badge-applications">{{ $pendingApplicationsCount }}</span>
                            @endif
                        </a>
                        <a href="#role-delegations"
                           x-bind:class="active === 'role-delegations' ? 'bg-orange-50 text-orange-900' : 'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700'"
                           x-bind:aria-current="active === 'role-delegations' ? 'true' : null"
                           data-testid="sidebar-link-role-delegations"
                           class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg font-medium transition-colors">
                            <span>{{ __('Role delegations') }}</span>
                            @if ($delegationsActiveCount > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-800" data-testid="sidebar-badge-delegations">{{ $delegationsActiveCount }}</span>
                            @endif
                        </a>
                        <a href="#password"
                           x-bind:class="active === 'password' ? 'bg-orange-50 text-orange-900' : 'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700'"
                           x-bind:aria-current="active === 'password' ? 'true' : null"
                           data-testid="sidebar-link-password"
                           class="block px-3 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Update password') }}
                        </a>
                        <div class="my-1 border-t border-stone-100 dark:border-stone-800"></div>
                        <a href="#danger-zone"
                           x-bind:class="active === 'danger-zone' ? 'bg-rose-100 text-rose-900' : 'text-rose-700 dark:text-rose-400 hover:bg-rose-50'"
                           x-bind:aria-current="active === 'danger-zone' ? 'true' : null"
                           data-testid="sidebar-link-danger-zone"
                           class="block px-3 py-2 rounded-lg font-medium transition-colors">
                            {{ __('Delete account') }}
                        </a>
                    </nav>
                </aside>

                {{-- ── Content panes (one per section, all rendered) ── --}}
                <main class="space-y-6 min-w-0">
                    <section id="profile-information" class="scroll-mt-24 p-4 sm:p-8 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </section>

                    <section id="preferences" class="scroll-mt-24 p-4 sm:p-8 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl">
                        <div class="max-w-xl">
                            @include('profile.partials.update-preferences-form')
                        </div>
                    </section>

                    <section id="role-applications" class="scroll-mt-24 p-4 sm:p-8 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl">
                        <div class="max-w-2xl">
                            @include('profile.partials.request-role-form')
                        </div>
                    </section>

                    <section id="role-delegations" class="scroll-mt-24 p-4 sm:p-8 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl">
                        <div class="max-w-2xl">
                            @include('profile.partials.delegate-role-form')
                        </div>
                    </section>

                    <section id="password" class="scroll-mt-24 p-4 sm:p-8 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </section>

                    <section id="danger-zone" class="scroll-mt-24 p-4 sm:p-8 bg-white dark:bg-stone-950 border border-rose-200 rounded-2xl">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</x-site-layout>
